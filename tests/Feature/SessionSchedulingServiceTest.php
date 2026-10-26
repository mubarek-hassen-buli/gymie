<?php

declare(strict_types=1);

use App\Enums\SessionStatus;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\PersonalTraining\SessionSchedulingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
});

it('schedules a training session when member has an active ongoing subscription', function (): void {
    $member = Member::factory()->create();
    $trainer = User::factory()->create();
    $trainer->assignRole('trainer');

    $gymService = Service::create(['name' => 'Personal Coaching']);
    $plan = Plan::create([
        'name' => 'Monthly Coaching',
        'service_id' => $gymService->id,
        'amount' => 300,
        'days' => 30,
    ]);

    $subscription = Subscription::create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'trainer_id' => $trainer->id,
        'start_date' => Carbon::today(),
        'end_date' => Carbon::today()->addDays(30),
        'status' => 'ongoing',
    ]);

    $service = new SessionSchedulingService();
    $sessionDate = Carbon::today()->addDays(5)->setHour(10)->setMinute(0)->setSecond(0);

    $session = $service->schedule(
        member: $member,
        trainer: $trainer,
        scheduledAt: $sessionDate,
        durationMinutes: 60,
        trainerNotes: 'Initial fitness assessment',
    );

    expect($session)->toBeInstanceOf(TrainingSession::class)
        ->and($session->member_id)->toBe($member->id)
        ->and($session->trainer_id)->toBe($trainer->id)
        ->and($session->subscription_id)->toBe($subscription->id)
        ->and($session->status)->toBe(SessionStatus::Scheduled)
        ->and($session->duration_minutes)->toBe(60)
        ->and($session->trainer_notes)->toBe('Initial fitness assessment');

    $this->assertDatabaseHas('training_sessions', [
        'id' => $session->id,
        'member_id' => $member->id,
        'trainer_id' => $trainer->id,
        'status' => 'scheduled',
    ]);
});

it('prevents scheduling when member has no active subscription on the target date', function (): void {
    $member = Member::factory()->create();
    $trainer = User::factory()->create();
    $trainer->assignRole('trainer');

    $service = new SessionSchedulingService();
    $sessionDate = Carbon::today()->addDays(2)->setHour(14)->setMinute(0)->setSecond(0);

    expect(fn () => $service->schedule(
        member: $member,
        trainer: $trainer,
        scheduledAt: $sessionDate,
    ))->toThrow(ValidationException::class);
});

it('prevents trainer double-booking when time windows overlap', function (): void {
    $member1 = Member::factory()->create();
    $member2 = Member::factory()->create();
    $trainer = User::factory()->create();
    $trainer->assignRole('trainer');

    $gymService = Service::create(['name' => 'PT']);
    $plan = Plan::create(['name' => 'PT Plan', 'service_id' => $gymService->id, 'amount' => 100, 'days' => 30]);

    Subscription::create([
        'member_id' => $member1->id,
        'plan_id' => $plan->id,
        'trainer_id' => $trainer->id,
        'start_date' => Carbon::today(),
        'end_date' => Carbon::today()->addDays(30),
        'status' => 'ongoing',
    ]);

    Subscription::create([
        'member_id' => $member2->id,
        'plan_id' => $plan->id,
        'trainer_id' => $trainer->id,
        'start_date' => Carbon::today(),
        'end_date' => Carbon::today()->addDays(30),
        'status' => 'ongoing',
    ]);

    $service = new SessionSchedulingService();
    $bookingTime = Carbon::tomorrow()->setHour(9)->setMinute(0)->setSecond(0);

    // Book first member 9:00 - 10:00
    $service->schedule(
        member: $member1,
        trainer: $trainer,
        scheduledAt: $bookingTime,
        durationMinutes: 60,
    );

    // Attempt overlapping booking 9:30 - 10:30 for trainer with second member
    $overlappingTime = $bookingTime->copy()->addMinutes(30);

    expect(fn () => $service->schedule(
        member: $member2,
        trainer: $trainer,
        scheduledAt: $overlappingTime,
        durationMinutes: 60,
    ))->toThrow(ValidationException::class);
});

it('prevents member double-booking across different trainers', function (): void {
    $member = Member::factory()->create();
    $trainer1 = User::factory()->create();
    $trainer1->assignRole('trainer');
    $trainer2 = User::factory()->create();
    $trainer2->assignRole('trainer');

    $gymService = Service::create(['name' => 'PT']);
    $plan = Plan::create(['name' => 'PT Plan', 'service_id' => $gymService->id, 'amount' => 100, 'days' => 30]);

    Subscription::create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'trainer_id' => $trainer1->id,
        'start_date' => Carbon::today(),
        'end_date' => Carbon::today()->addDays(30),
        'status' => 'ongoing',
    ]);

    $service = new SessionSchedulingService();
    $bookingTime = Carbon::tomorrow()->setHour(11)->setMinute(0)->setSecond(0);

    // Book with trainer 1 at 11:00
    $service->schedule(
        member: $member,
        trainer: $trainer1,
        scheduledAt: $bookingTime,
        durationMinutes: 60,
    );

    // Attempt booking with trainer 2 at 11:15
    $conflictTime = $bookingTime->copy()->addMinutes(15);

    expect(fn () => $service->schedule(
        member: $member,
        trainer: $trainer2,
        scheduledAt: $conflictTime,
        durationMinutes: 60,
    ))->toThrow(ValidationException::class);
});

it('completes, marks absent, and cancels sessions cleanly with notes', function (): void {
    $member = Member::factory()->create();
    $trainer = User::factory()->create();
    $trainer->assignRole('trainer');

    $gymService = Service::create(['name' => 'PT']);
    $plan = Plan::create(['name' => 'PT Plan', 'service_id' => $gymService->id, 'amount' => 100, 'days' => 30]);

    $subscription = Subscription::create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'trainer_id' => $trainer->id,
        'start_date' => Carbon::today(),
        'end_date' => Carbon::today()->addDays(30),
        'status' => 'ongoing',
    ]);

    $service = new SessionSchedulingService();

    // 1. Test Complete Session
    $session1 = $service->schedule(
        member: $member,
        trainer: $trainer,
        scheduledAt: Carbon::today()->setHour(8)->setMinute(0)->setSecond(0),
        durationMinutes: 45,
    );

    $completed = $service->completeSession(
        session: $session1,
        trainerNotes: 'Great form on squats',
        clientFeedback: 'Felt challenging but rewarding',
    );

    expect($completed->status)->toBe(SessionStatus::Completed)
        ->and($completed->trainer_notes)->toBe('Great form on squats')
        ->and($completed->client_feedback)->toBe('Felt challenging but rewarding');

    // 2. Test Mark Absent
    $session2 = $service->schedule(
        member: $member,
        trainer: $trainer,
        scheduledAt: Carbon::today()->setHour(10)->setMinute(0)->setSecond(0),
        durationMinutes: 45,
    );

    $absent = $service->markAbsent($session2, 'Member did not show up or notify');
    expect($absent->status)->toBe(SessionStatus::MemberAbsent)
        ->and($absent->trainer_notes)->toBe('Member did not show up or notify');

    // 3. Test Cancel by Member
    $session3 = $service->schedule(
        member: $member,
        trainer: $trainer,
        scheduledAt: Carbon::today()->setHour(12)->setMinute(0)->setSecond(0),
        durationMinutes: 45,
    );

    $cancelled = $service->cancelSession($session3, cancelledBy: 'member', reason: 'Flu symptoms');
    expect($cancelled->status)->toBe(SessionStatus::CancelledByMember)
        ->and($cancelled->trainer_notes)->toContain('Flu symptoms');
});

it('calculates member attendance and adherence metrics accurately', function (): void {
    $member = Member::factory()->create();
    $trainer = User::factory()->create();
    $trainer->assignRole('trainer');

    // Create 3 completed sessions and 1 absent session in the last 10 days
    for ($i = 1; $i <= 3; $i++) {
        TrainingSession::create([
            'member_id' => $member->id,
            'trainer_id' => $trainer->id,
            'scheduled_at' => Carbon::now()->subDays($i),
            'duration_minutes' => 60,
            'status' => SessionStatus::Completed->value,
        ]);
    }

    TrainingSession::create([
        'member_id' => $member->id,
        'trainer_id' => $trainer->id,
        'scheduled_at' => Carbon::now()->subDays(5),
        'duration_minutes' => 60,
        'status' => SessionStatus::MemberAbsent->value,
    ]);

    $service = new SessionSchedulingService();
    $metrics = $service->getAttendanceMetrics($member, days: 30);

    // Total = 4, completed = 3, absent = 1, adherence = 75.0%
    expect($metrics['total'])->toBe(4)
        ->and($metrics['completed'])->toBe(3)
        ->and($metrics['absent'])->toBe(1)
        ->and($metrics['adherence_percentage'])->toBe(75.0);
});
