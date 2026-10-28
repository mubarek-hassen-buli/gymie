<?php

declare(strict_types=1);

use App\Enums\ProgramStatus;
use App\Enums\SessionStatus;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Members\MemberResource;
use App\Filament\Resources\TrainingSessions\TrainingSessionResource;
use App\Filament\Resources\WorkoutPrograms\WorkoutProgramResource;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\TrainingSession;
use App\Models\User;
use App\Models\WorkoutProgram;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
});

it('scopes training sessions according to user role and ownership', function (): void {
    $trainerA = User::factory()->create(['name' => 'Trainer A']);
    $trainerA->assignRole('trainer');

    $trainerB = User::factory()->create(['name' => 'Trainer B']);
    $trainerB->assignRole('trainer');

    $clientUserA = User::factory()->create(['name' => 'Client A']);
    $clientUserA->assignRole('member');
    $memberA = Member::factory()->create(['user_id' => $clientUserA->id]);

    $clientUserB = User::factory()->create(['name' => 'Client B']);
    $clientUserB->assignRole('member');
    $memberB = Member::factory()->create(['user_id' => $clientUserB->id]);

    $sessionA = TrainingSession::create([
        'member_id' => $memberA->id,
        'trainer_id' => $trainerA->id,
        'scheduled_at' => Carbon::now()->addDay(),
        'duration_minutes' => 60,
        'status' => SessionStatus::Scheduled->value,
    ]);

    $sessionB = TrainingSession::create([
        'member_id' => $memberB->id,
        'trainer_id' => $trainerB->id,
        'scheduled_at' => Carbon::now()->addDay(),
        'duration_minutes' => 60,
        'status' => SessionStatus::Scheduled->value,
    ]);

    // 1. Trainer A only sees Session A
    $this->actingAs($trainerA);
    $trainerAQuery = TrainingSessionResource::getEloquentQuery()->pluck('id');
    expect($trainerAQuery)->toContain($sessionA->id)
        ->not->toContain($sessionB->id);

    // 2. Trainer B only sees Session B
    $this->actingAs($trainerB);
    $trainerBQuery = TrainingSessionResource::getEloquentQuery()->pluck('id');
    expect($trainerBQuery)->toContain($sessionB->id)
        ->not->toContain($sessionA->id);

    // 3. Member A only sees Session A
    $this->actingAs($clientUserA);
    $memberAQuery = TrainingSessionResource::getEloquentQuery()->pluck('id');
    expect($memberAQuery)->toContain($sessionA->id)
        ->not->toContain($sessionB->id);

    // 4. Super Admin sees both sessions
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    $this->actingAs($admin);
    $adminQuery = TrainingSessionResource::getEloquentQuery()->pluck('id');
    expect($adminQuery)->toContain($sessionA->id, $sessionB->id);

    // 5. Front Desk sees both sessions
    $frontDesk = User::factory()->create();
    $frontDesk->assignRole('front_desk');
    $this->actingAs($frontDesk);
    $frontDeskQuery = TrainingSessionResource::getEloquentQuery()->pluck('id');
    expect($frontDeskQuery)->toContain($sessionA->id, $sessionB->id);
});

it('scopes workout programs strictly to trainer or member ownership', function (): void {
    $trainer1 = User::factory()->create();
    $trainer1->assignRole('trainer');

    $trainer2 = User::factory()->create();
    $trainer2->assignRole('trainer');

    $clientUser1 = User::factory()->create();
    $clientUser1->assignRole('member');
    $member1 = Member::factory()->create(['user_id' => $clientUser1->id]);

    $clientUser2 = User::factory()->create();
    $clientUser2->assignRole('member');
    $member2 = Member::factory()->create(['user_id' => $clientUser2->id]);

    $program1 = WorkoutProgram::create([
        'title' => 'Routine 1',
        'member_id' => $member1->id,
        'trainer_id' => $trainer1->id,
        'status' => ProgramStatus::Active,
    ]);

    $program2 = WorkoutProgram::create([
        'title' => 'Routine 2',
        'member_id' => $member2->id,
        'trainer_id' => $trainer2->id,
        'status' => ProgramStatus::Active,
    ]);

    // Trainer 1 scoping
    $this->actingAs($trainer1);
    $trainer1Programs = WorkoutProgramResource::getEloquentQuery()->pluck('id');
    expect($trainer1Programs)->toContain($program1->id)
        ->not->toContain($program2->id);

    // Member 1 scoping
    $this->actingAs($clientUser1);
    $member1Programs = WorkoutProgramResource::getEloquentQuery()->pluck('id');
    expect($member1Programs)->toContain($program1->id)
        ->not->toContain($program2->id);

    // Super admin unscoped
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    $this->actingAs($admin);
    $adminPrograms = WorkoutProgramResource::getEloquentQuery()->pluck('id');
    expect($adminPrograms)->toContain($program1->id, $program2->id);
});

it('scopes members directory for trainers to only their actively assigned clients', function (): void {
    $trainer = User::factory()->create();
    $trainer->assignRole('trainer');

    $gymService = Service::create(['name' => 'PT']);
    $plan = Plan::create(['name' => 'Plan', 'service_id' => $gymService->id, 'amount' => 100, 'days' => 30]);

    $assignedMember = Member::factory()->create(['name' => 'Assigned Client']);
    $unassignedMember = Member::factory()->create(['name' => 'General Gym Member']);

    // Create ongoing subscription assigned to $trainer
    Subscription::create([
        'member_id' => $assignedMember->id,
        'plan_id' => $plan->id,
        'trainer_id' => $trainer->id,
        'start_date' => Carbon::today(),
        'end_date' => Carbon::today()->addDays(30),
        'status' => 'ongoing',
    ]);

    // 1. Trainer only sees assigned client
    $this->actingAs($trainer);
    $trainerMembers = MemberResource::getEloquentQuery()->pluck('id');
    expect($trainerMembers)->toContain($assignedMember->id)
        ->not->toContain($unassignedMember->id);

    // 2. Front Desk sees both members
    $frontDesk = User::factory()->create();
    $frontDesk->assignRole('front_desk');
    $this->actingAs($frontDesk);
    $frontDeskMembers = MemberResource::getEloquentQuery()->pluck('id');
    expect($frontDeskMembers)->toContain($assignedMember->id, $unassignedMember->id);
});

it('scopes invoices so members can only view their own invoices', function (): void {
    $clientUser1 = User::factory()->create();
    $clientUser1->assignRole('member');
    $member1 = Member::factory()->create(['user_id' => $clientUser1->id]);

    $clientUser2 = User::factory()->create();
    $clientUser2->assignRole('member');
    $member2 = Member::factory()->create(['user_id' => $clientUser2->id]);

    $gymService = Service::create(['name' => 'Gym']);
    $plan = Plan::create(['name' => 'Regular', 'service_id' => $gymService->id, 'amount' => 50, 'days' => 30]);

    $sub1 = Subscription::create([
        'member_id' => $member1->id,
        'plan_id' => $plan->id,
        'start_date' => Carbon::today(),
        'end_date' => Carbon::today()->addDays(30),
        'status' => 'ongoing',
    ]);

    $sub2 = Subscription::create([
        'member_id' => $member2->id,
        'plan_id' => $plan->id,
        'start_date' => Carbon::today(),
        'end_date' => Carbon::today()->addDays(30),
        'status' => 'ongoing',
    ]);

    $inv1 = Invoice::create([
        'subscription_id' => $sub1->id,
        'number' => 'INV-001',
        'date' => Carbon::today(),
        'total_amount' => 50.0,
        'status' => 'paid',
    ]);

    $inv2 = Invoice::create([
        'subscription_id' => $sub2->id,
        'number' => 'INV-002',
        'date' => Carbon::today(),
        'total_amount' => 50.0,
        'status' => 'paid',
    ]);

    // Member 1 only sees Invoice 1
    $this->actingAs($clientUser1);
    $member1Invoices = InvoiceResource::getEloquentQuery()->pluck('id');
    expect($member1Invoices)->toContain($inv1->id)
        ->not->toContain($inv2->id);

    // Front Desk sees both invoices
    $frontDesk = User::factory()->create();
    $frontDesk->assignRole('front_desk');
    $this->actingAs($frontDesk);
    $frontDeskInvoices = InvoiceResource::getEloquentQuery()->pluck('id');
    expect($frontDeskInvoices)->toContain($inv1->id, $inv2->id);
});

it('scopes subscriptions so trainers only see their client subscriptions and members only see their own', function (): void {
    $trainer = User::factory()->create();
    $trainer->assignRole('trainer');

    $otherTrainer = User::factory()->create();
    $otherTrainer->assignRole('trainer');

    $clientUser = User::factory()->create();
    $clientUser->assignRole('member');
    $member = Member::factory()->create(['user_id' => $clientUser->id]);

    $otherClientUser = User::factory()->create();
    $otherClientUser->assignRole('member');
    $otherMember = Member::factory()->create(['user_id' => $otherClientUser->id]);

    $service = Service::create(['name' => 'PT Service']);
    $plan = Plan::create(['name' => 'PT Plan', 'service_id' => $service->id, 'amount' => 150, 'days' => 30]);

    $sub1 = Subscription::create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'trainer_id' => $trainer->id,
        'start_date' => Carbon::today(),
        'end_date' => Carbon::today()->addDays(30),
        'status' => 'ongoing',
    ]);

    $sub2 = Subscription::create([
        'member_id' => $otherMember->id,
        'plan_id' => $plan->id,
        'trainer_id' => $otherTrainer->id,
        'start_date' => Carbon::today(),
        'end_date' => Carbon::today()->addDays(30),
        'status' => 'ongoing',
    ]);

    // Trainer only sees sub1
    $this->actingAs($trainer);
    $trainerSubs = App\Filament\Resources\Subscriptions\SubscriptionResource::getEloquentQuery()->pluck('id');
    expect($trainerSubs)->toContain($sub1->id)
        ->not->toContain($sub2->id);

    // Member only sees sub1
    $this->actingAs($clientUser);
    $memberSubs = App\Filament\Resources\Subscriptions\SubscriptionResource::getEloquentQuery()->pluck('id');
    expect($memberSubs)->toContain($sub1->id)
        ->not->toContain($sub2->id);

    // Front Desk sees both
    $frontDesk = User::factory()->create();
    $frontDesk->assignRole('front_desk');
    $this->actingAs($frontDesk);
    $frontDeskSubs = App\Filament\Resources\Subscriptions\SubscriptionResource::getEloquentQuery()->pluck('id');
    expect($frontDeskSubs)->toContain($sub1->id, $sub2->id);
});

