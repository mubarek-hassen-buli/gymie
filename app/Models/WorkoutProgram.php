<?php

namespace App\Models;

use App\Enums\ProgramStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $title
 * @property int $member_id
 * @property int|null $trainer_id
 * @property int|null $subscription_id
 * @property string|null $goal
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property ProgramStatus $status
 * @property string|null $notes
 * @property-read Member $member
 * @property-read User|null $trainer
 * @property-read Subscription|null $subscription
 * @property-read Collection<int, WorkoutProgramExercise> $exercises
 */
class WorkoutProgram extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'member_id',
        'trainer_id',
        'subscription_id',
        'goal',
        'start_date',
        'end_date',
        'status',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => ProgramStatus::class,
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
     * @return HasMany<WorkoutProgramExercise, $this>
     */
    public function exercises(): HasMany
    {
        return $this->hasMany(WorkoutProgramExercise::class)->orderBy('sort_order');
    }
}
