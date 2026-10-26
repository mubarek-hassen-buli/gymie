<?php

namespace App\Models;

use App\Enums\SessionStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $subscription_id
 * @property int $member_id
 * @property int $trainer_id
 * @property Carbon $scheduled_at
 * @property int $duration_minutes
 * @property SessionStatus $status
 * @property string|null $trainer_notes
 * @property string|null $client_feedback
 * @property-read Member $member
 * @property-read User $trainer
 * @property-read Subscription|null $subscription
 * @property-read Collection<int, SessionWorkoutLog> $workoutLogs
 */
class TrainingSession extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'subscription_id',
        'member_id',
        'trainer_id',
        'scheduled_at',
        'duration_minutes',
        'status',
        'trainer_notes',
        'client_feedback',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'duration_minutes' => 'integer',
            'status' => SessionStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    /**
     * @return BelongsTo<Subscription, $this>
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * @return HasMany<SessionWorkoutLog, $this>
     */
    public function workoutLogs(): HasMany
    {
        return $this->hasMany(SessionWorkoutLog::class);
    }

    /**
     * Check if this session is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === SessionStatus::Completed;
    }

    /**
     * Scope to completed sessions only.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', SessionStatus::Completed->value);
    }

    /**
     * Scope to upcoming/scheduled sessions.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', SessionStatus::Scheduled->value);
    }
}
