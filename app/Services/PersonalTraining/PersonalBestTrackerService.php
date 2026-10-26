<?php

namespace App\Services\PersonalTraining;

use App\Models\Exercise;
use App\Models\Member;
use App\Models\SessionWorkoutLog;
use App\Models\TrainingSession;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Domain service tracking personal records (PRs) and exercise progression.
 */
class PersonalBestTrackerService
{
    /**
     * Record a workout set for a training session and auto-detect Personal Records.
     */
    public function logSet(
        TrainingSession $session,
        Exercise $exercise,
        int $setNumber,
        float $weightKg,
        int $reps,
        ?float $rpe = null,
        ?string $notes = null
    ): SessionWorkoutLog {
        return DB::transaction(function () use ($session, $exercise, $setNumber, $weightKg, $reps, $rpe, $notes): SessionWorkoutLog {
            $memberId = $session->member_id;

            // Historical highest weight logged for this member on this exercise
            $currentPrWeight = (float) SessionWorkoutLog::query()
                ->whereHas('trainingSession', fn ($q) => $q->where('member_id', $memberId))
                ->where('exercise_id', $exercise->id)
                ->max('weight_kg');

            $isPr = ($weightKg > 0) && ($weightKg > $currentPrWeight);

            return SessionWorkoutLog::create([
                'training_session_id' => $session->id,
                'exercise_id' => $exercise->id,
                'set_number' => $setNumber,
                'weight_kg' => $weightKg,
                'reps_completed' => $reps,
                'rpe' => $rpe,
                'is_pr' => $isPr,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * Get all personal records (PRs) achieved by a member across all exercises.
     *
     * @return Collection<int, array{exercise_id: int, exercise_name: string, muscle_group: string, max_weight_kg: float, date: string}>
     */
    public function getPersonalBests(Member $member): Collection
    {
        return SessionWorkoutLog::query()
            ->join('training_sessions', 'session_workout_logs.training_session_id', '=', 'training_sessions.id')
            ->join('exercises', 'session_workout_logs.exercise_id', '=', 'exercises.id')
            ->where('training_sessions.member_id', $member->id)
            ->where('session_workout_logs.is_pr', true)
            ->select([
                'exercises.id as exercise_id',
                'exercises.name as exercise_name',
                'exercises.muscle_group as muscle_group',
                DB::raw('MAX(session_workout_logs.weight_kg) as max_weight_kg'),
                DB::raw('MAX(training_sessions.scheduled_at) as achieved_at'),
            ])
            ->groupBy('exercises.id', 'exercises.name', 'exercises.muscle_group')
            ->orderBy('max_weight_kg', 'desc')
            ->get()
            ->map(fn ($row): array => [
                'exercise_id' => (int) $row->exercise_id,
                'exercise_name' => (string) $row->exercise_name,
                'muscle_group' => (string) $row->muscle_group,
                'max_weight_kg' => (float) $row->max_weight_kg,
                'date' => (string) $row->achieved_at,
            ]);
    }

    /**
     * Get progression timeline for a specific exercise and member.
     *
     * @return Collection<int, array{date: string, weight_kg: float, reps: int, is_pr: bool}>
     */
    public function getExerciseProgression(Member $member, Exercise $exercise): Collection
    {
        return SessionWorkoutLog::query()
            ->join('training_sessions', 'session_workout_logs.training_session_id', '=', 'training_sessions.id')
            ->where('training_sessions.member_id', $member->id)
            ->where('session_workout_logs.exercise_id', $exercise->id)
            ->orderBy('training_sessions.scheduled_at', 'asc')
            ->select([
                'training_sessions.scheduled_at as date',
                'session_workout_logs.weight_kg',
                'session_workout_logs.reps_completed as reps',
                'session_workout_logs.is_pr',
            ])
            ->get()
            ->map(fn ($row): array => [
                'date' => (string) $row->date,
                'weight_kg' => (float) $row->weight_kg,
                'reps' => (int) $row->reps,
                'is_pr' => (bool) $row->is_pr,
            ]);
    }
}
