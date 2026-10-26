<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $workout_program_id
 * @property string $day_label
 * @property int $exercise_id
 * @property int $sort_order
 * @property int $target_sets
 * @property string $target_reps
 * @property string|null $target_rpe
 * @property int $rest_seconds
 * @property string|null $notes
 * @property-read WorkoutProgram $workoutProgram
 * @property-read Exercise $exercise
 */
class WorkoutProgramExercise extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'workout_program_id',
        'day_label',
        'exercise_id',
        'sort_order',
        'target_sets',
        'target_reps',
        'target_rpe',
        'rest_seconds',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'target_sets' => 'integer',
            'target_rpe' => 'decimal:1',
            'rest_seconds' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<WorkoutProgram, $this>
     */
    public function workoutProgram(): BelongsTo
    {
        return $this->belongsTo(WorkoutProgram::class);
    }

    /**
     * @return BelongsTo<Exercise, $this>
     */
    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}
