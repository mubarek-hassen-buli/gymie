<?php

declare(strict_types=1);

use App\Enums\EquipmentType;
use App\Enums\MuscleGroup;
use App\Enums\ProgramStatus;
use App\Enums\SessionStatus;
use App\Filament\Resources\Exercises\ExerciseResource;
use App\Filament\Resources\Members\MemberResource;
use App\Filament\Resources\Members\RelationManagers\TrainingSessionsRelationManager;
use App\Filament\Resources\Members\RelationManagers\WorkoutProgramsRelationManager;
use App\Filament\Resources\TrainingSessions\TrainingSessionResource;
use App\Filament\Resources\WorkoutPrograms\WorkoutProgramResource;
use App\Models\Exercise;
use App\Models\Member;
use App\Models\TrainingSession;
use App\Models\User;
use App\Models\WorkoutProgram;
use App\Policies\ExercisePolicy;
use App\Policies\TrainingSessionPolicy;
use App\Policies\WorkoutProgramPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    Role::findOrCreate('super_admin', 'web');
    Role::findOrCreate('trainer', 'web');
});

it('verifies MemberResource has personal training relation managers registered', function (): void {
    $relations = MemberResource::getRelations();

    expect($relations)
        ->toContain(TrainingSessionsRelationManager::class)
        ->toContain(WorkoutProgramsRelationManager::class);
});

it('allows super admin to view exercise list page', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    Exercise::create([
        'name' => 'Barbell Bench Press',
        'muscle_group' => MuscleGroup::Chest,
        'equipment_type' => EquipmentType::Barbell,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->get(ExerciseResource::getUrl('index'))
        ->assertOk()
        ->assertSee('Barbell Bench Press');
});

it('allows super admin to view workout programs list page', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $member = Member::factory()->create(['name' => 'Jane Fitness']);

    WorkoutProgram::create([
        'title' => 'Strength Foundation Block',
        'member_id' => $member->id,
        'trainer_id' => $admin->id,
        'status' => ProgramStatus::Active,
    ]);

    $this->actingAs($admin)
        ->get(WorkoutProgramResource::getUrl('index'))
        ->assertOk()
        ->assertSee('Strength Foundation Block')
        ->assertSee('Jane Fitness');
});

it('allows super admin to view training sessions list page', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $member = Member::factory()->create(['name' => 'Bob Builder']);

    TrainingSession::create([
        'member_id' => $member->id,
        'trainer_id' => $admin->id,
        'scheduled_at' => now()->addDay(),
        'duration_minutes' => 60,
        'status' => SessionStatus::Scheduled,
    ]);

    $this->actingAs($admin)
        ->get(TrainingSessionResource::getUrl('index'))
        ->assertOk()
        ->assertSee('Bob Builder');
});

it('evaluates policies correctly for personal training models', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $exercisePolicy = new ExercisePolicy();
    $programPolicy = new WorkoutProgramPolicy();
    $sessionPolicy = new TrainingSessionPolicy();

    $exercise = Exercise::create([
        'name' => 'Squat',
        'muscle_group' => MuscleGroup::Legs,
        'equipment_type' => EquipmentType::Barbell,
        'is_active' => true,
    ]);

    $member = Member::factory()->create();

    $program = WorkoutProgram::create([
        'title' => 'Leg Day Hypertrophy',
        'member_id' => $member->id,
        'status' => ProgramStatus::Draft,
    ]);

    $session = TrainingSession::create([
        'member_id' => $member->id,
        'trainer_id' => $admin->id,
        'scheduled_at' => now(),
        'duration_minutes' => 45,
        'status' => SessionStatus::Scheduled,
    ]);

    expect($exercisePolicy->viewAny($admin))->toBeTrue();
    expect($exercisePolicy->create($admin))->toBeTrue();
    expect($exercisePolicy->view($admin, $exercise))->toBeTrue();

    expect($programPolicy->viewAny($admin))->toBeTrue();
    expect($programPolicy->view($admin, $program))->toBeTrue();

    expect($sessionPolicy->viewAny($admin))->toBeTrue();
    expect($sessionPolicy->view($admin, $session))->toBeTrue();
});
