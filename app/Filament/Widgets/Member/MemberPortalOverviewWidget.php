<?php

declare(strict_types=1);

namespace App\Filament\Widgets\Member;

use App\Enums\ProgramStatus;
use App\Enums\SessionStatus;
use App\Models\Subscription;
use App\Models\TrainingSession;
use App\Models\WorkoutProgram;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Overview portal widget for gym members showing active membership and schedule.
 */
class MemberPortalOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -50;

    protected ?string $pollingInterval = '30s';

    protected int|string|array $columnSpan = 'full';

    /**
     * @var int | array<string, ?int> | null
     */
    protected int|array|null $columns = 4;

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->isMember();
    }

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $user = auth()->user();
        $member = $user?->member;

        if (! $member) {
            return [
                Stat::make('Portal Profile', 'Unlinked Member')
                    ->description('No member profile linked to this account.')
                    ->color('danger'),
            ];
        }

        // 1. Active Subscription & Plan
        $activeSubscription = Subscription::query()
            ->with(['plan'])
            ->where('member_id', $member->id)
            ->where('status', 'ongoing')
            ->first();

        $planName = $activeSubscription?->plan?->name ?? 'No Active Plan';
        $daysLeft = $activeSubscription && $activeSubscription->end_date
            ? max(0, (int) Carbon::today()->diffInDays($activeSubscription->end_date, false))
            : 0;

        $planDescription = $activeSubscription
            ? "{$daysLeft} days remaining (expires {$activeSubscription->end_date->format('M j')})"
            : 'Visit reception to renew';

        // 2. Dedicated Coach
        $activeTrainer = $member->activeTrainer();
        $coachName = $activeTrainer?->name ?? 'None Assigned';
        $coachDescription = $activeTrainer ? 'Your personal fitness coach' : 'General gym access';

        // 3. Next Upcoming Training Session
        $nextSession = TrainingSession::query()
            ->with(['trainer'])
            ->where('member_id', $member->id)
            ->where('scheduled_at', '>=', Carbon::now())
            ->where('status', SessionStatus::Scheduled->value)
            ->orderBy('scheduled_at', 'asc')
            ->first();

        $nextSessionText = $nextSession
            ? $nextSession->scheduled_at->format('D, M j @ g:i A')
            : 'No session booked';

        $nextSessionDescription = $nextSession
            ? "With {$nextSession->trainer?->name}"
            : 'Book at front desk or with coach';

        // 4. Active Workout Routine
        $activeProgram = WorkoutProgram::query()
            ->withCount('exercises')
            ->where('member_id', $member->id)
            ->where('status', ProgramStatus::Active)
            ->first();

        $routineTitle = $activeProgram?->title ?? 'No Active Routine';
        $routineDescription = $activeProgram
            ? "{$activeProgram->exercises_count} prescribed exercises"
            : 'Ask coach to assign program';

        return [
            Stat::make('Membership Plan', $planName)
                ->description($planDescription)
                ->descriptionIcon('heroicon-m-ticket')
                ->color($activeSubscription ? 'success' : 'gray'),

            Stat::make('Assigned Coach', $coachName)
                ->description($coachDescription)
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color($activeTrainer ? 'primary' : 'gray'),

            Stat::make('Next Session', $nextSessionText)
                ->description($nextSessionDescription)
                ->descriptionIcon('heroicon-m-clock')
                ->color($nextSession ? 'warning' : 'gray'),

            Stat::make('Workout Program', $routineTitle)
                ->description($routineDescription)
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color($activeProgram ? 'info' : 'gray'),
        ];
    }
}
