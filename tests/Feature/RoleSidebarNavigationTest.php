<?php

declare(strict_types=1);

use App\Filament\Widgets\Trainer\TrainerStatsWidget;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\TrainerProfile;
use App\Models\TrainingSession;
use App\Models\User;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
});

it('hides unauthorized navigation items and empty groups for front_desk', function (): void {
    $frontDesk = User::factory()->create();
    $frontDesk->assignRole('front_desk');

    $this->actingAs($frontDesk);

    $panel = Filament::getCurrentOrDefaultPanel();
    $navigation = $panel->buildNavigation();

    $visibleGroupLabels = collect($navigation)->map(fn (NavigationGroup $g): string => (string) $g->getLabel())->all();
    $visibleItemLabels = collect($navigation)
        ->flatMap(fn (NavigationGroup $g): array => collect($g->getItems())
            ->filter(fn (NavigationItem $i): bool => $i->isVisible())
            ->map(fn (NavigationItem $i): string => (string) $i->getLabel())
            ->all()
        )->all();

    // Administration group must be completely absent
    expect($visibleGroupLabels)->not->toContain(__('app.navigation.groups.administration'));

    // Expenses must be hidden
    expect($visibleItemLabels)->not->toContain(__('app.resources.expenses.plural'));
    expect($visibleItemLabels)->not->toContain(__('app.resources.users.plural'));
    expect($visibleItemLabels)->not->toContain(__('app.settings.title'));

    // Authorized items must be visible
    expect($visibleItemLabels)->toContain(__('app.resources.members.plural'));
    expect($visibleItemLabels)->toContain(__('app.resources.subscriptions.plural'));
    expect($visibleItemLabels)->toContain(__('app.resources.invoices.plural'));
    expect($visibleItemLabels)->toContain(__('app.resources.enquiries.plural'));
});

it('hides sales, billing, and administration navigation groups for trainer', function (): void {
    $trainer = User::factory()->create();
    $trainer->assignRole('trainer');

    $this->actingAs($trainer);

    $panel = Filament::getCurrentOrDefaultPanel();
    $navigation = $panel->buildNavigation();

    $visibleGroupLabels = collect($navigation)->map(fn (NavigationGroup $g): string => (string) $g->getLabel())->all();
    $visibleItemLabels = collect($navigation)
        ->flatMap(fn (NavigationGroup $g): array => collect($g->getItems())
            ->filter(fn (NavigationItem $i): bool => $i->isVisible())
            ->map(fn (NavigationItem $i): string => (string) $i->getLabel())
            ->all()
        )->all();

    // Sales, Billing, and Administration groups must be completely hidden
    expect($visibleGroupLabels)->not->toContain(__('app.navigation.groups.sales'));
    expect($visibleGroupLabels)->not->toContain(__('app.navigation.groups.billing'));
    expect($visibleGroupLabels)->not->toContain(__('app.navigation.groups.administration'));

    // Training resources must be visible
    expect($visibleItemLabels)->toContain(__('app.resources.training_sessions.plural'));
    expect($visibleItemLabels)->toContain(__('app.resources.workout_programs.plural'));
    expect($visibleItemLabels)->toContain(__('app.resources.exercises.plural'));
    expect($visibleItemLabels)->toContain(__('app.resources.members.plural'));
});

it('hides staff and management groups for member', function (): void {
    $clientUser = User::factory()->create();
    $clientUser->assignRole('member');
    Member::factory()->create(['user_id' => $clientUser->id]);

    $this->actingAs($clientUser);

    $panel = Filament::getCurrentOrDefaultPanel();
    $navigation = $panel->buildNavigation();

    $visibleGroupLabels = collect($navigation)->map(fn (NavigationGroup $g): string => (string) $g->getLabel())->all();
    $visibleItemLabels = collect($navigation)
        ->flatMap(fn (NavigationGroup $g): array => collect($g->getItems())
            ->filter(fn (NavigationItem $i): bool => $i->isVisible())
            ->map(fn (NavigationItem $i): string => (string) $i->getLabel())
            ->all()
        )->all();

    // Staff-only groups must be completely absent
    expect($visibleGroupLabels)->not->toContain(__('app.navigation.groups.sales'));
    expect($visibleGroupLabels)->not->toContain(__('app.navigation.groups.administration'));
    expect($visibleGroupLabels)->not->toContain(__('app.navigation.groups.memberships'));

    // Member can view training sessions, workout programs, exercises, and invoices
    expect($visibleItemLabels)->toContain(__('app.resources.training_sessions.plural'));
    expect($visibleItemLabels)->toContain(__('app.resources.workout_programs.plural'));
    expect($visibleItemLabels)->toContain(__('app.resources.exercises.plural'));
    expect($visibleItemLabels)->toContain(__('app.resources.invoices.plural'));
});

it('renders all navigation groups for super_admin', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin);

    $panel = Filament::getCurrentOrDefaultPanel();
    $navigation = $panel->buildNavigation();

    $visibleGroupLabels = collect($navigation)->map(fn (NavigationGroup $g): string => (string) $g->getLabel())->all();

    expect($visibleGroupLabels)->toContain(__('app.navigation.groups.sales'));
    expect($visibleGroupLabels)->toContain(__('app.navigation.groups.memberships'));
    expect($visibleGroupLabels)->toContain(__('app.navigation.groups.training'));
    expect($visibleGroupLabels)->toContain(__('app.navigation.groups.billing'));
    expect($visibleGroupLabels)->toContain(__('app.navigation.groups.administration'));
});

it('renders TrainerStatsWidget without TypeError for authenticated trainer', function (): void {
    $trainer = User::factory()->create();
    $trainer->assignRole('trainer');

    TrainerProfile::create([
        'user_id' => $trainer->id,
        'max_active_clients' => 15,
        'is_accepting_clients' => true,
    ]);

    $service = Service::create(['name' => 'Fitness Coaching']);
    $plan = Plan::create(['name' => 'PT 10', 'service_id' => $service->id, 'amount' => 200, 'days' => 30]);
    $client = Member::factory()->create();

    Subscription::create([
        'member_id' => $client->id,
        'plan_id' => $plan->id,
        'trainer_id' => $trainer->id,
        'start_date' => Carbon::today(),
        'end_date' => Carbon::today()->addDays(30),
        'status' => 'ongoing',
    ]);

    TrainingSession::create([
        'member_id' => $client->id,
        'trainer_id' => $trainer->id,
        'scheduled_at' => Carbon::now()->addHours(2),
        'duration_minutes' => 60,
        'status' => 'scheduled',
    ]);

    $this->actingAs($trainer);

    // Assert the component renders cleanly without any TypeError
    Livewire::test(TrainerStatsWidget::class)
        ->assertSuccessful()
        ->assertSee('Active Clients')
        ->assertSee("Today's Sessions")
        ->assertSee('Capacity Utilization');
});

