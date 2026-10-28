<?php

declare(strict_types=1);

use App\Filament\Pages\Dashboard;
use App\Filament\Pages\Settings;
use App\Filament\Widgets\Analytics\CashflowTrendChartWidget;
use App\Filament\Widgets\Analytics\ExpenseCategoriesDoughnutChartWidget;
use App\Filament\Widgets\Analytics\FinancialMetricsWidget;
use App\Filament\Widgets\Analytics\MembershipMetricsWidget;
use App\Filament\Widgets\Analytics\MembershipOverviewSubscriptionsTableWidget;
use App\Filament\Widgets\Analytics\RecentTransactionsTableWidget;
use App\Filament\Widgets\Member\MemberPortalOverviewWidget;
use App\Filament\Widgets\Member\MemberUpcomingSessionsTableWidget;
use App\Filament\Widgets\Trainer\TrainerStatsWidget;
use App\Filament\Widgets\Trainer\TrainerTodaySessionsTableWidget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
});

it('restricts financial and spending widgets to super_admin only', function (): void {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');

    $frontDesk = User::factory()->create();
    $frontDesk->assignRole('front_desk');

    $trainer = User::factory()->create();
    $trainer->assignRole('trainer');

    $member = User::factory()->create();
    $member->assignRole('member');

    // Super Admin can view all financial widgets
    $this->actingAs($superAdmin);
    expect(FinancialMetricsWidget::canView())->toBeTrue();
    expect(ExpenseCategoriesDoughnutChartWidget::canView())->toBeTrue();
    expect(RecentTransactionsTableWidget::canView())->toBeTrue();
    expect(CashflowTrendChartWidget::canView())->toBeTrue();

    // Front Desk cannot view financial metrics
    $this->actingAs($frontDesk);
    expect(FinancialMetricsWidget::canView())->toBeFalse();
    expect(ExpenseCategoriesDoughnutChartWidget::canView())->toBeFalse();
    expect(RecentTransactionsTableWidget::canView())->toBeFalse();
    expect(CashflowTrendChartWidget::canView())->toBeFalse();

    // Trainer cannot view financial metrics
    $this->actingAs($trainer);
    expect(FinancialMetricsWidget::canView())->toBeFalse();
    expect(ExpenseCategoriesDoughnutChartWidget::canView())->toBeFalse();
    expect(RecentTransactionsTableWidget::canView())->toBeFalse();
    expect(CashflowTrendChartWidget::canView())->toBeFalse();

    // Member cannot view financial metrics
    $this->actingAs($member);
    expect(FinancialMetricsWidget::canView())->toBeFalse();
    expect(ExpenseCategoriesDoughnutChartWidget::canView())->toBeFalse();
    expect(RecentTransactionsTableWidget::canView())->toBeFalse();
    expect(CashflowTrendChartWidget::canView())->toBeFalse();
});

it('allows super_admin and front_desk to view membership overview widgets but blocks trainer and member', function (): void {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');

    $frontDesk = User::factory()->create();
    $frontDesk->assignRole('front_desk');

    $trainer = User::factory()->create();
    $trainer->assignRole('trainer');

    $member = User::factory()->create();
    $member->assignRole('member');

    $this->actingAs($superAdmin);
    expect(MembershipMetricsWidget::canView())->toBeTrue();
    expect(MembershipOverviewSubscriptionsTableWidget::canView())->toBeTrue();

    $this->actingAs($frontDesk);
    expect(MembershipMetricsWidget::canView())->toBeTrue();
    expect(MembershipOverviewSubscriptionsTableWidget::canView())->toBeTrue();

    $this->actingAs($trainer);
    expect(MembershipMetricsWidget::canView())->toBeFalse();
    expect(MembershipOverviewSubscriptionsTableWidget::canView())->toBeFalse();

    $this->actingAs($member);
    expect(MembershipMetricsWidget::canView())->toBeFalse();
    expect(MembershipOverviewSubscriptionsTableWidget::canView())->toBeFalse();
});

it('restricts trainer widgets exclusively to trainer role', function (): void {
    $trainer = User::factory()->create();
    $trainer->assignRole('trainer');

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');

    $frontDesk = User::factory()->create();
    $frontDesk->assignRole('front_desk');

    $member = User::factory()->create();
    $member->assignRole('member');

    $this->actingAs($trainer);
    expect(TrainerStatsWidget::canView())->toBeTrue();
    expect(TrainerTodaySessionsTableWidget::canView())->toBeTrue();

    $this->actingAs($superAdmin);
    expect(TrainerStatsWidget::canView())->toBeFalse();
    expect(TrainerTodaySessionsTableWidget::canView())->toBeFalse();

    $this->actingAs($frontDesk);
    expect(TrainerStatsWidget::canView())->toBeFalse();
    expect(TrainerTodaySessionsTableWidget::canView())->toBeFalse();

    $this->actingAs($member);
    expect(TrainerStatsWidget::canView())->toBeFalse();
    expect(TrainerTodaySessionsTableWidget::canView())->toBeFalse();
});

it('restricts member portal widgets exclusively to member role', function (): void {
    $member = User::factory()->create();
    $member->assignRole('member');

    $trainer = User::factory()->create();
    $trainer->assignRole('trainer');

    $frontDesk = User::factory()->create();
    $frontDesk->assignRole('front_desk');

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');

    $this->actingAs($member);
    expect(MemberPortalOverviewWidget::canView())->toBeTrue();
    expect(MemberUpcomingSessionsTableWidget::canView())->toBeTrue();

    $this->actingAs($trainer);
    expect(MemberPortalOverviewWidget::canView())->toBeFalse();
    expect(MemberUpcomingSessionsTableWidget::canView())->toBeFalse();

    $this->actingAs($frontDesk);
    expect(MemberPortalOverviewWidget::canView())->toBeFalse();
    expect(MemberUpcomingSessionsTableWidget::canView())->toBeFalse();

    $this->actingAs($superAdmin);
    expect(MemberPortalOverviewWidget::canView())->toBeFalse();
    expect(MemberUpcomingSessionsTableWidget::canView())->toBeFalse();
});

it('restricts system settings page strictly to super_admin', function (): void {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');

    $frontDesk = User::factory()->create();
    $frontDesk->assignRole('front_desk');

    $trainer = User::factory()->create();
    $trainer->assignRole('trainer');

    $member = User::factory()->create();
    $member->assignRole('member');

    $this->actingAs($superAdmin);
    expect(Settings::canAccess())->toBeTrue();

    $this->actingAs($frontDesk);
    expect(Settings::canAccess())->toBeFalse();

    $this->actingAs($trainer);
    expect(Settings::canAccess())->toBeFalse();

    $this->actingAs($member);
    expect(Settings::canAccess())->toBeFalse();
});
