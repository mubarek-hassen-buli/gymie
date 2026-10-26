<?php

namespace App\Models;

use App\Enums\EquipmentType;
use App\Enums\MuscleGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property MuscleGroup $muscle_group
 * @property EquipmentType $equipment_type
 * @property string|null $description
 * @property string|null $instructions
 * @property string|null $video_url
 * @property bool $is_active
 * @property-read Collection<int, SessionWorkoutLog> $workoutLogs
 */
class Exercise extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'muscle_group',
        'equipment_type',
        'description',
        'instructions',
        'video_url',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'muscle_group' => MuscleGroup::class,
            'equipment_type' => EquipmentType::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<SessionWorkoutLog, $this>
     */
    public function workoutLogs(): HasMany
    {
        return $this->hasMany(SessionWorkoutLog::class);
    }

    /**
     * @return HasMany<WorkoutProgramExercise, $this>
     */
    public function programExercises(): HasMany
    {
        return $this->hasMany(WorkoutProgramExercise::class);
    }

    /**
     * Scope to active exercises only.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by muscle group.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeByMuscleGroup(Builder $query, MuscleGroup|string $group): Builder
    {
        $value = $group instanceof MuscleGroup ? $group->value : $group;

        return $query->where('muscle_group', $value);
    }
}
