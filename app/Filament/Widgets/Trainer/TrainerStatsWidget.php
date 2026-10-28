<?php

declare(strict_types=1);

namespace App\Filament\Widgets\Trainer;

use App\Enums\SessionStatus;
use App\Models\Subscription;
use App\Models\TrainingSession;
use App\Services\PersonalTraining\TrainerCapacityService;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Dedicated KPI overview widget for personal trainers.
 */
class TrainerStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -50;

    protected int|string|array $columnSpan = 'full';

    /**
     * @var int | array<string, ?int> | null
     */
    protected int|array|null $columns = 4;

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->isTrainer();
    }

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        // 1. Active Clients assigned to this trainer
        $activeClientsCount = Subscription::query()
            ->where('trainer_id', $user->id)
            ->where('status', 'ongoing')
            ->distinct('member_id')
            ->count('member_id');

        // 2. Today's scheduled sessions
        $todaySessionsCount = TrainingSession::query()
            ->where('trainer_id', $user->id)
            ->whereDate('scheduled_at', Carbon::today())
            ->count();

        // 3. Sessions completed this calendar month
        $completedThisMonth = TrainingSession::query()
            ->where('trainer_id', $user->id)
            ->where('status', SessionStatus::Completed->value)
            ->whereBetween('scheduled_at', [
                Carbon::today()->startOfMonth(),
                Carbon::today()->endOfMonth(),
            ])
            ->count();

        // 4. Capacity & Workload
        $workload = app(TrainerCapacityService::class)->getWorkload($user->id);
        $maxClients = $workload['max_clients'] > 0 ? $workload['max_clients'] : 15;
        $utilization = $workload['utilization_percentage'];

        return [
            Stat::make('Active Clients', (string) $activeClientsCount)
                ->description('Assigned ongoing memberships')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),

            Stat::make("Today's Sessions", (string) $todaySessionsCount)
                ->description('Booked for '.Carbon::today()->format('D, M j'))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($todaySessionsCount > 0 ? 'warning' : 'gray'),

            Stat::make('Completed This Month', (string) $completedThisMonth)
                ->description('Finished coaching sessions')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('Capacity Utilization', "{$utilization}%")
                ->description("{$activeClientsCount} / {$maxClients} active clients")
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color($utilization >= 90.0 ? 'danger' : 'info'),
        ];
    }
}
