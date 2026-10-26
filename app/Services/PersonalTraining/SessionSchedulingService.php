<?php

namespace App\Services\PersonalTraining;

use App\Enums\SessionStatus;
use App\Models\Member;
use App\Models\Subscription;
use App\Models\TrainingSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Domain service for managing personal training session schedules and attendance.
 */
class SessionSchedulingService
{
    /**
     * Schedule a new training session with conflict checking and transactional locking.
     *
     * @throws ValidationException
     */
    public function schedule(
        Member $member,
        User $trainer,
        Carbon $scheduledAt,
        int $durationMinutes = 60,
        ?Subscription $subscription = null,
        ?string $trainerNotes = null
    ): TrainingSession {
        return DB::transaction(function () use ($member, $trainer, $scheduledAt, $durationMinutes, $subscription, $trainerNotes): TrainingSession {
            $endAt = $scheduledAt->copy()->addMinutes($durationMinutes);

            // 1. Verify Member has an active subscription if subscription is enforced
            $activeSubscription = $subscription ?? $member->subscriptions()
                ->where('status', 'ongoing')
                ->whereDate('start_date', '<=', $scheduledAt)
                ->whereDate('end_date', '>=', $scheduledAt)
                ->latest('start_date')
                ->first();

            if (! $activeSubscription && $subscription === null) {
                // If member has no active subscription at that date, warn or block
                throw ValidationException::withMessages([
                    'scheduled_at' => [
                        __('Member :name does not have an active subscription for :date.', [
                            'name' => $member->name,
                            'date' => $scheduledAt->toFormattedDateString(),
                        ]),
                    ],
                ]);
            }

            $driver = DB::connection()->getDriverName();
            $overlapExpression = $this->overlapExpression($driver);

            // 2. Check for Trainer schedule conflict (prevent double-booking)
            $trainerConflict = TrainingSession::query()
                ->where('trainer_id', $trainer->id)
                ->where('status', SessionStatus::Scheduled->value)
                ->where(function ($query) use ($scheduledAt, $endAt, $overlapExpression) {
                    $query->whereBetween('scheduled_at', [$scheduledAt, $endAt->copy()->subMinute()])
                        ->orWhere(function ($q) use ($scheduledAt, $overlapExpression) {
                            $q->where('scheduled_at', '<=', $scheduledAt)
                                ->whereRaw($overlapExpression, [$scheduledAt->toDateTimeString()]);
                        });
                })
                ->lockForUpdate()
                ->exists();

            if ($trainerConflict) {
                throw ValidationException::withMessages([
                    'scheduled_at' => [
                        __('Trainer :name is already booked during this time window.', ['name' => $trainer->name]),
                    ],
                ]);
            }

            // 3. Check for Member schedule conflict
            $memberConflict = TrainingSession::query()
                ->where('member_id', $member->id)
                ->where('status', SessionStatus::Scheduled->value)
                ->where(function ($query) use ($scheduledAt, $endAt, $overlapExpression) {
                    $query->whereBetween('scheduled_at', [$scheduledAt, $endAt->copy()->subMinute()])
                        ->orWhere(function ($q) use ($scheduledAt, $overlapExpression) {
                            $q->where('scheduled_at', '<=', $scheduledAt)
                                ->whereRaw($overlapExpression, [$scheduledAt->toDateTimeString()]);
                        });
                })
                ->lockForUpdate()
                ->exists();

            if ($memberConflict) {
                throw ValidationException::withMessages([
                    'scheduled_at' => [
                        __('Member :name already has another session booked at this time.', ['name' => $member->name]),
                    ],
                ]);
            }

            return TrainingSession::create([
                'subscription_id' => $activeSubscription?->id,
                'member_id' => $member->id,
                'trainer_id' => $trainer->id,
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => $durationMinutes,
                'status' => SessionStatus::Scheduled->value,
                'trainer_notes' => $trainerNotes,
            ]);
        }, 3);
    }

    /**
     * Mark a scheduled session as completed.
     */
    public function completeSession(
        TrainingSession $session,
        ?string $trainerNotes = null,
        ?string $clientFeedback = null
    ): TrainingSession {
        $session->update([
            'status' => SessionStatus::Completed->value,
            'trainer_notes' => $trainerNotes ?? $session->trainer_notes,
            'client_feedback' => $clientFeedback ?? $session->client_feedback,
        ]);

        return $session->refresh();
    }

    /**
     * Mark a session as member absent (no-show).
     */
    public function markAbsent(TrainingSession $session, ?string $notes = null): TrainingSession
    {
        $session->update([
            'status' => SessionStatus::MemberAbsent->value,
            'trainer_notes' => $notes ?? $session->trainer_notes,
        ]);

        return $session->refresh();
    }

    /**
     * Cancel a session.
     */
    public function cancelSession(
        TrainingSession $session,
        string $cancelledBy = 'member',
        ?string $reason = null
    ): TrainingSession {
        $status = $cancelledBy === 'trainer'
            ? SessionStatus::CancelledByTrainer->value
            : SessionStatus::CancelledByMember->value;

        $session->update([
            'status' => $status,
            'trainer_notes' => $reason ? ($session->trainer_notes ? $session->trainer_notes . ' | ' . $reason : $reason) : $session->trainer_notes,
        ]);

        return $session->refresh();
    }

    /**
     * Calculate adherence / attendance rate for a member.
     *
     * @return array{total: int, completed: int, absent: int, adherence_percentage: float}
     */
    public function getAttendanceMetrics(Member $member, int $days = 30): array
    {
        $since = Carbon::now()->subDays($days);

        $sessions = TrainingSession::query()
            ->where('member_id', $member->id)
            ->where('scheduled_at', '>=', $since)
            ->whereIn('status', [
                SessionStatus::Completed->value,
                SessionStatus::MemberAbsent->value,
            ])
            ->get();

        $total = $sessions->count();
        $completed = $sessions->filter(fn ($s): bool => $s->status === SessionStatus::Completed || $s->status?->value === SessionStatus::Completed->value)->count();
        $absent = $sessions->filter(fn ($s): bool => $s->status === SessionStatus::MemberAbsent || $s->status?->value === SessionStatus::MemberAbsent->value)->count();

        $rate = $total > 0 ? round(($completed / $total) * 100, 1) : 100.0;

        return [
            'total' => $total,
            'completed' => $completed,
            'absent' => $absent,
            'adherence_percentage' => $rate,
        ];
    }

    /**
     * Database-agnostic expression checking if scheduled_at + duration_minutes > target time.
     */
    private function overlapExpression(string $driver): string
    {
        return match ($driver) {
            'sqlite' => "datetime(scheduled_at, '+' || duration_minutes || ' minute') > ?",
            'pgsql' => "(scheduled_at + (duration_minutes || ' minutes')::interval) > ?",
            default => 'DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE) > ?',
        };
    }
}
