<?php

declare(strict_types=1);

use App\Models\Member;
use App\Models\Plan;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\User;
use App\Services\PersonalTraining\TrainerCapacityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
});

it('determines if a trainer can accept clients based on capacity and status', function (): void {
    $trainer = User::factory()->create(['name' => 'Coach John']);
    $trainer->trainerProfile()->create([
        'max_active_clients' => 2,
        'is_accepting_clients' => true,
    ]);

    $service = new TrainerCapacityService();

    expect($service->canAcceptClient($trainer))->toBeTrue();

    // Fill up capacity
    $gymService = Service::create(['name' => 'Personal Training']);
    $plan = Plan::create(['name' => '1-Month PT', 'service_id' => $gymService->id, 'amount' => 200, 'days' => 30]);

    $member1 = Member::factory()->create();
    $member2 = Member::factory()->create();

    Subscription::create([
        'member_id' => $member1->id,
        'plan_id' => $plan->id,
        'trainer_id' => $trainer->id,
        'start_date' => Carbon::today(),
        'end_date' => Carbon::today()->addDays(30),
        'status' => 'ongoing',
    ]);

    expect($service->canAcceptClient($trainer))->toBeTrue();

    Subscription::create([
        'member_id' => $member2->id,
        'plan_id' => $plan->id,
        'trainer_id' => $trainer->id,
        'start_date' => Carbon::today(),
        'end_date' => Carbon::today()->addDays(30),
        'status' => 'ongoing',
    ]);

    // Now capacity limit is reached
    expect($service->canAcceptClient($trainer))->toBeFalse();

    // Toggle is_accepting_clients to false
    $trainer->trainerProfile->update(['is_accepting_clients' => false]);
    expect($service->canAcceptClient($trainer))->toBeFalse();
});

it('calculates trainer workload and utilization percentage correctly', function (): void {
    $trainer = User::factory()->create(['name' => 'Coach Sarah']);
    $trainer->trainerProfile()->create([
        'max_active_clients' => 4,
        'is_accepting_clients' => true,
    ]);

    $gymService = Service::create(['name' => 'PT']);
    $plan = Plan::create(['name' => 'PT Plan', 'service_id' => $gymService->id, 'amount' => 150, 'days' => 30]);
    $member = Member::factory()->create();

    Subscription::create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'trainer_id' => $trainer->id,
        'start_date' => Carbon::today(),
        'end_date' => Carbon::today()->addDays(30),
        'status' => 'ongoing',
    ]);

    $service = new TrainerCapacityService();
    $workload = $service->getWorkload($trainer);

    expect($workload)->toMatchArray([
        'active_clients' => 1,
        'max_clients' => 4,
        'available_slots' => 3,
        'utilization_percentage' => 25.0,
        'is_accepting_clients' => true,
    ]);
});

it('returns only trainers who currently have available capacity', function (): void {
    $trainerFull = User::factory()->create();
    $trainerFull->assignRole('trainer');
    $trainerFull->trainerProfile()->create(['max_active_clients' => 1, 'is_accepting_clients' => true]);

    $trainerOpen = User::factory()->create();
    $trainerOpen->assignRole('trainer');
    $trainerOpen->trainerProfile()->create(['max_active_clients' => 5, 'is_accepting_clients' => true]);

    $gymService = Service::create(['name' => 'PT']);
    $plan = Plan::create(['name' => 'PT Plan', 'service_id' => $gymService->id, 'amount' => 150, 'days' => 30]);
    $member = Member::factory()->create();

    Subscription::create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'trainer_id' => $trainerFull->id,
        'start_date' => Carbon::today(),
        'end_date' => Carbon::today()->addDays(30),
        'status' => 'ongoing',
    ]);

    $service = new TrainerCapacityService();
    $availableTrainers = $service->getAvailableTrainers();

    expect($availableTrainers->pluck('id'))->toContain($trainerOpen->id)
        ->not->toContain($trainerFull->id);
});
