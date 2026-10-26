<?php

declare(strict_types=1);

use App\Enums\EquipmentType;
use App\Enums\MuscleGroup;
use App\Enums\ProgramStatus;
use App\Models\Exercise;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WorkoutProgram;
use App\Models\WorkoutProgramExercise;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
});

it('creates a workout program with multiple exercises and day labels', function (): void {
    $member = Member::factory()->create();
    $trainer = User::factory()->create(['name' => 'Coach Marcus']);
    $trainer->assignRole('trainer');

    $program = WorkoutProgram::create([
        'title' => 'Hypertrophy Phase 1',
        'member_id' => $member->id,
        'trainer_id' => $trainer->id,
        'goal' => 'Muscle Gain',
        'start_date' => Carbon::today(),
        'end_date' => Carbon::today()->addWeeks(8),
        'status' => ProgramStatus::Active,
        'notes' => 'Progressive overload each week',
    ]);

    $squat = Exercise::create([
        'name' => 'Barbell Back Squat',
        'muscle_group' => MuscleGroup::Legs,
        'equipment_type' => EquipmentType::Barbell,
        'is_compound' => true,
    ]);

    $bench = Exercise::create([
        'name' => 'Incline Dumbbell Press',
        'muscle_group' => MuscleGroup::Chest,
        'equipment_type' => EquipmentType::Dumbbell,
        'is_compound' => false,
    ]);

    $item1 = WorkoutProgramExercise::create([
        'workout_program_id' => $program->id,
        'day_label' => 'Day 1: Legs & Core',
        'exercise_id' => $squat->id,
        'sort_order' => 1,
        'target_sets' => 4,
        'target_reps' => '8-10',
        'target_rpe' => 8.0,
        'rest_seconds' => 120,
    ]);

    $item2 = WorkoutProgramExercise::create([
        'workout_program_id' => $program->id,
        'day_label' => 'Day 2: Push',
        'exercise_id' => $bench->id,
        'sort_order' => 1,
        'target_sets' => 3,
        'target_reps' => '10-12',
        'target_rpe' => 7.5,
        'rest_seconds' => 90,
    ]);

    expect($program->exercises)->toHaveCount(2)
        ->and($program->member->id)->toBe($member->id)
        ->and($program->trainer->id)->toBe($trainer->id)
        ->and($program->status)->toBe(ProgramStatus::Active);

    // Verify cascade deletion of program items when program is deleted
    $programId = $program->id;
    $program->delete();

    expect(WorkoutProgramExercise::where('workout_program_id', $programId)->count())->toBe(0);
});

it('filters workout programs by active scope', function (): void {
    $member = Member::factory()->create();

    $active = WorkoutProgram::create([
        'title' => 'Active Program',
        'member_id' => $member->id,
        'status' => ProgramStatus::Active,
    ]);

    $completed = WorkoutProgram::create([
        'title' => 'Completed Program',
        'member_id' => $member->id,
        'status' => ProgramStatus::Completed,
    ]);

    $activePrograms = WorkoutProgram::active()->get();

    expect($activePrograms->pluck('id'))->toContain($active->id)
        ->not->toContain($completed->id);
});

it('returns trainers correctly formatted via User::getTrainerOptions', function (): void {
    $trainer1 = User::factory()->create(['name' => 'Alice Coach']);
    $trainer1->assignRole('trainer');

    $trainer2 = User::factory()->create(['name' => 'Bob Trainer']);
    $trainer2->assignRole('trainer');

    $nonTrainer = User::factory()->create(['name' => 'Front Desk Staff']);

    $options = User::getTrainerOptions();

    expect($options)->toHaveKey($trainer1->id, 'Alice Coach')
        ->and($options)->toHaveKey($trainer2->id, 'Bob Trainer')
        ->and($options)->not->toHaveKey($nonTrainer->id);
});

it('resolves activeTrainer for a member based on ongoing subscription', function (): void {
    $member = Member::factory()->create();
    $trainer = User::factory()->create(['name' => 'Coach Dan']);
    $trainer->assignRole('trainer');

    $gymService = Service::create(['name' => 'PT']);
    $plan = Plan::create(['name' => 'PT Plan', 'service_id' => $gymService->id, 'amount' => 100, 'days' => 30]);

    // Active subscription
    Subscription::create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'trainer_id' => $trainer->id,
        'start_date' => Carbon::today(),
        'end_date' => Carbon::today()->addDays(30),
        'status' => 'ongoing',
    ]);

    $activeTrainer = $member->activeTrainer();

    expect($activeTrainer)->not->toBeNull()
        ->and($activeTrainer?->id)->toBe($trainer->id)
        ->and($activeTrainer?->name)->toBe('Coach Dan');
});
