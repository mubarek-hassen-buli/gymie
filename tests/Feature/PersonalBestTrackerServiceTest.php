<?php

declare(strict_types=1);

use App\Enums\EquipmentType;
use App\Enums\MuscleGroup;
use App\Enums\SessionStatus;
use App\Models\Exercise;
use App\Models\Member;
use App\Models\SessionWorkoutLog;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\PersonalTraining\PersonalBestTrackerService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
});

it('flags the initial set of an exercise as a personal record', function (): void {
    $member = Member::factory()->create();
    $trainer = User::factory()->create();
    $trainer->assignRole('trainer');

    $exercise = Exercise::create([
        'name' => 'Barbell Bench Press',
        'muscle_group' => MuscleGroup::Chest,
        'equipment_type' => EquipmentType::Barbell,
        'is_compound' => true,
    ]);

    $session = TrainingSession::create([
        'member_id' => $member->id,
        'trainer_id' => $trainer->id,
        'scheduled_at' => Carbon::today()->setHour(10)->setMinute(0),
        'duration_minutes' => 60,
        'status' => SessionStatus::Scheduled->value,
    ]);

    $service = new PersonalBestTrackerService();
    $log = $service->logSet(
        session: $session,
        exercise: $exercise,
        setNumber: 1,
        weightKg: 80.0,
        reps: 8,
        rpe: 8.0,
        notes: 'Good clean lockout',
    );

    expect($log)->toBeInstanceOf(SessionWorkoutLog::class)
        ->and($log->is_pr)->toBeTrue()
        ->and((float) $log->weight_kg)->toBe(80.0)
        ->and($log->reps_completed)->toBe(8)
        ->and((float) $log->rpe)->toBe(8.0);

    $this->assertDatabaseHas('session_workout_logs', [
        'id' => $log->id,
        'is_pr' => true,
        'weight_kg' => 80.0,
    ]);
});

it('correctly detects new PR when weight exceeds prior records and ignores equal/lower weight', function (): void {
    $member = Member::factory()->create();
    $trainer = User::factory()->create();
    $trainer->assignRole('trainer');

    $exercise = Exercise::create([
        'name' => 'Deadlift',
        'muscle_group' => MuscleGroup::Back,
        'equipment_type' => EquipmentType::Barbell,
        'is_compound' => true,
    ]);

    $session = TrainingSession::create([
        'member_id' => $member->id,
        'trainer_id' => $trainer->id,
        'scheduled_at' => Carbon::today()->setHour(10)->setMinute(0),
        'duration_minutes' => 60,
        'status' => SessionStatus::Scheduled->value,
    ]);

    $service = new PersonalBestTrackerService();

    // Set 1: 100 kg -> PR
    $set1 = $service->logSet($session, $exercise, 1, 100.0, 5);
    expect($set1->is_pr)->toBeTrue();

    // Set 2: 100 kg (equal weight) -> NOT a new PR
    $set2 = $service->logSet($session, $exercise, 2, 100.0, 5);
    expect($set2->is_pr)->toBeFalse();

    // Set 3: 90 kg (lower weight drop-set) -> NOT a new PR
    $set3 = $service->logSet($session, $exercise, 3, 90.0, 8);
    expect($set3->is_pr)->toBeFalse();

    // Set 4: 110 kg (heavier) -> NEW PR
    $set4 = $service->logSet($session, $exercise, 4, 110.0, 3);
    expect($set4->is_pr)->toBeTrue();
});

it('isolates personal records strictly per member', function (): void {
    $memberA = Member::factory()->create();
    $memberB = Member::factory()->create();
    $trainer = User::factory()->create();
    $trainer->assignRole('trainer');

    $exercise = Exercise::create([
        'name' => 'Overhead Press',
        'muscle_group' => MuscleGroup::Shoulders,
        'equipment_type' => EquipmentType::Barbell,
    ]);

    $sessionA = TrainingSession::create([
        'member_id' => $memberA->id,
        'trainer_id' => $trainer->id,
        'scheduled_at' => Carbon::today()->setHour(9)->setMinute(0),
        'duration_minutes' => 60,
        'status' => SessionStatus::Scheduled->value,
    ]);

    $sessionB = TrainingSession::create([
        'member_id' => $memberB->id,
        'trainer_id' => $trainer->id,
        'scheduled_at' => Carbon::today()->setHour(11)->setMinute(0),
        'duration_minutes' => 60,
        'status' => SessionStatus::Scheduled->value,
    ]);

    $service = new PersonalBestTrackerService();

    // Member A logs 70 kg -> PR for Member A
    $setA = $service->logSet($sessionA, $exercise, 1, 70.0, 5);
    expect($setA->is_pr)->toBeTrue();

    // Member B logs 50 kg -> should still be PR for Member B (unaffected by Member A's 70 kg)
    $setB = $service->logSet($sessionB, $exercise, 1, 50.0, 5);
    expect($setB->is_pr)->toBeTrue();
});

it('retrieves member personal bests and exercise progression timeline', function (): void {
    $member = Member::factory()->create();
    $trainer = User::factory()->create();
    $trainer->assignRole('trainer');

    $squat = Exercise::create([
        'name' => 'Back Squat',
        'muscle_group' => MuscleGroup::Legs,
        'equipment_type' => EquipmentType::Barbell,
        'is_compound' => true,
    ]);

    $bench = Exercise::create([
        'name' => 'Bench Press',
        'muscle_group' => MuscleGroup::Chest,
        'equipment_type' => EquipmentType::Barbell,
        'is_compound' => true,
    ]);

    $session1 = TrainingSession::create([
        'member_id' => $member->id,
        'trainer_id' => $trainer->id,
        'scheduled_at' => Carbon::now()->subDays(7),
        'duration_minutes' => 60,
        'status' => SessionStatus::Completed->value,
    ]);

    $session2 = TrainingSession::create([
        'member_id' => $member->id,
        'trainer_id' => $trainer->id,
        'scheduled_at' => Carbon::now()->subDays(1),
        'duration_minutes' => 60,
        'status' => SessionStatus::Completed->value,
    ]);

    $service = new PersonalBestTrackerService();

    // Day -7: Squat 120kg, Bench 80kg
    $service->logSet($session1, $squat, 1, 120.0, 5);
    $service->logSet($session1, $bench, 1, 80.0, 5);

    // Day -1: Squat 130kg (new PR), Bench 80kg (not PR)
    $service->logSet($session2, $squat, 1, 130.0, 5);
    $service->logSet($session2, $bench, 1, 80.0, 5);

    // Test getPersonalBests
    $prs = $service->getPersonalBests($member);
    expect($prs)->toHaveCount(2);

    $squatPr = $prs->firstWhere('exercise_id', $squat->id);
    expect($squatPr)->not->toBeNull()
        ->and($squatPr['max_weight_kg'])->toBe(130.0)
        ->and($squatPr['exercise_name'])->toBe('Back Squat');

    $benchPr = $prs->firstWhere('exercise_id', $bench->id);
    expect($benchPr)->not->toBeNull()
        ->and($benchPr['max_weight_kg'])->toBe(80.0);

    // Test getExerciseProgression
    $progression = $service->getExerciseProgression($member, $squat);
    expect($progression)->toHaveCount(2)
        ->and($progression[0]['weight_kg'])->toBe(120.0)
        ->and($progression[0]['is_pr'])->toBeTrue()
        ->and($progression[1]['weight_kg'])->toBe(130.0)
        ->and($progression[1]['is_pr'])->toBeTrue();
});
