<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $training_session_id
 * @property int $exercise_id
 * @property int $set_number
 * @property string $weight_kg
 * @property int $reps_completed
 * @property string|null $rpe
 * @property bool $is_pr
 * @property string|null $notes
 * @property-read TrainingSession $trainingSession
 * @property-read Exercise $exercise
 */
class SessionWorkoutLog extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'training_session_id',
        'exercise_id',
        'set_number',
        'weight_kg',
        'reps_completed',
        'rpe',
        'is_pr',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'set_number' => 'integer',
            'weight_kg' => 'decimal:2',
            'reps_completed' => 'integer',
            'rpe' => 'decimal:1',
            'is_pr' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<TrainingSession, $this>
     */
    public function trainingSession(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class);
    }

    /**
     * @return BelongsTo<Exercise, $this>
     */
    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}
