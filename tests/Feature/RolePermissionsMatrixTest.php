<?php

declare(strict_types=1);

use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
});

it('links Member and User models via user_id relationship bidirectionally', function (): void {
    $user = User::factory()->create(['name' => 'John Gymie']);
    $user->assignRole('member');

    $member = Member::factory()->create([
        'user_id' => $user->id,
        'name' => 'John Gymie',
    ]);

    expect($member->user)->not->toBeNull()
        ->and($member->user?->id)->toBe($user->id)
        ->and($user->member)->not->toBeNull()
        ->and($user->member?->id)->toBe($member->id);

    // Test role inspection helper methods
    expect($user->isMember())->toBeTrue()
        ->and($user->isSuperAdmin())->toBeFalse()
        ->and($user->isFrontDesk())->toBeFalse()
        ->and($user->isTrainer())->toBeFalse();
});

it('correctly identifies user roles via model helper methods', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $frontDesk = User::factory()->create();
    $frontDesk->assignRole('front_desk');

    $trainer = User::factory()->create();
    $trainer->assignRole('trainer');

    $client = User::factory()->create();
    $client->assignRole('member');

    expect($admin->isSuperAdmin())->toBeTrue()
        ->and($frontDesk->isFrontDesk())->toBeTrue()
        ->and($trainer->isTrainer())->toBeTrue()
        ->and($client->isMember())->toBeTrue();
});

it('verifies front_desk role has front-of-house permissions but is denied sensitive modules', function (): void {
    $frontDesk = User::factory()->create();
    $frontDesk->assignRole('front_desk');

    // Permitted modules
    expect($frontDesk->can('ViewAny:Member'))->toBeTrue()
        ->and($frontDesk->can('Create:Member'))->toBeTrue()
        ->and($frontDesk->can('Update:Member'))->toBeTrue()
        ->and($frontDesk->can('ViewAny:Subscription'))->toBeTrue()
        ->and($frontDesk->can('Create:Subscription'))->toBeTrue()
        ->and($frontDesk->can('ViewAny:Invoice'))->toBeTrue()
        ->and($frontDesk->can('ViewAny:Enquiry'))->toBeTrue()
        ->and($frontDesk->can('ViewAny:FollowUp'))->toBeTrue()
        ->and($frontDesk->can('ViewAny:TrainingSession'))->toBeTrue()
        ->and($frontDesk->can('Create:TrainingSession'))->toBeTrue()
        ->and($frontDesk->can('View:MembershipMetricsWidget'))->toBeTrue()
        ->and($frontDesk->can('View:ExpiringSoonSubscriptionsTableWidget'))->toBeTrue();

    // Denied modules & destructive actions
    expect($frontDesk->can('Delete:Member'))->toBeFalse()
        ->and($frontDesk->can('Delete:Subscription'))->toBeFalse()
        ->and($frontDesk->can('ViewAny:Expense'))->toBeFalse()
        ->and($frontDesk->can('Create:Expense'))->toBeFalse()
        ->and($frontDesk->can('ViewAny:User'))->toBeFalse()
        ->and($frontDesk->can('ViewAny:Role'))->toBeFalse()
        ->and($frontDesk->can('View:Settings'))->toBeFalse()
        ->and($frontDesk->can('View:FinancialMetricsWidget'))->toBeFalse()
        ->and($frontDesk->can('View:RecentTransactionsTableWidget'))->toBeFalse();
});

it('verifies trainer role has coaching permissions but is denied billing, expenses, and settings', function (): void {
    $trainer = User::factory()->create();
    $trainer->assignRole('trainer');

    // Permitted coaching modules
    expect($trainer->can('ViewAny:TrainingSession'))->toBeTrue()
        ->and($trainer->can('Create:TrainingSession'))->toBeTrue()
        ->and($trainer->can('Update:TrainingSession'))->toBeTrue()
        ->and($trainer->can('ViewAny:WorkoutProgram'))->toBeTrue()
        ->and($trainer->can('Create:WorkoutProgram'))->toBeTrue()
        ->and($trainer->can('ViewAny:Exercise'))->toBeTrue()
        ->and($trainer->can('Create:Exercise'))->toBeTrue()
        ->and($trainer->can('ViewAny:Member'))->toBeTrue();

    // Denied financial & administrative modules
    expect($trainer->can('ViewAny:Invoice'))->toBeFalse()
        ->and($trainer->can('Create:Invoice'))->toBeFalse()
        ->and($trainer->can('ViewAny:Expense'))->toBeFalse()
        ->and($trainer->can('ViewAny:Subscription'))->toBeFalse()
        ->and($trainer->can('ViewAny:Enquiry'))->toBeFalse()
        ->and($trainer->can('ViewAny:Plan'))->toBeFalse()
        ->and($trainer->can('ViewAny:User'))->toBeFalse()
        ->and($trainer->can('ViewAny:Role'))->toBeFalse()
        ->and($trainer->can('View:Settings'))->toBeFalse();
});

it('verifies member role has read-only access to workouts, sessions, and invoices and is denied all staff actions', function (): void {
    $memberUser = User::factory()->create();
    $memberUser->assignRole('member');

    // Permitted read-only areas
    expect($memberUser->can('ViewAny:TrainingSession'))->toBeTrue()
        ->and($memberUser->can('ViewAny:WorkoutProgram'))->toBeTrue()
        ->and($memberUser->can('ViewAny:Exercise'))->toBeTrue()
        ->and($memberUser->can('ViewAny:Invoice'))->toBeTrue();

    // Denied write/mutate permissions
    expect($memberUser->can('Create:TrainingSession'))->toBeFalse()
        ->and($memberUser->can('Update:TrainingSession'))->toBeFalse()
        ->and($memberUser->can('Create:WorkoutProgram'))->toBeFalse()
        ->and($memberUser->can('Create:Exercise'))->toBeFalse();

    // Denied staff & administrative modules
    expect($memberUser->can('ViewAny:Member'))->toBeFalse()
        ->and($memberUser->can('ViewAny:Subscription'))->toBeFalse()
        ->and($memberUser->can('ViewAny:Enquiry'))->toBeFalse()
        ->and($memberUser->can('ViewAny:Expense'))->toBeFalse()
        ->and($memberUser->can('ViewAny:Plan'))->toBeFalse()
        ->and($memberUser->can('ViewAny:User'))->toBeFalse()
        ->and($memberUser->can('ViewAny:Role'))->toBeFalse()
        ->and($memberUser->can('View:Settings'))->toBeFalse();
});
