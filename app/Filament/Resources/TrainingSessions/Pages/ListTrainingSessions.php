<?php

declare(strict_types=1);

namespace App\Filament\Resources\TrainingSessions\Pages;

use App\Enums\SessionStatus;
use App\Filament\Resources\TrainingSessions\TrainingSessionResource;
use App\Models\TrainingSession;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTrainingSessions extends ListRecords
{
    protected static string $resource = TrainingSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label(__('app.actions.new', ['resource' => TrainingSessionResource::getModelLabel()]))
                ->hidden(! TrainingSession::exists()),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('app.common.all')),
            'scheduled' => Tab::make(SessionStatus::Scheduled->getLabel())
                ->badge(TrainingSession::query()->where('status', SessionStatus::Scheduled->value)->count())
                ->badgeColor(SessionStatus::Scheduled->getColor())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', SessionStatus::Scheduled->value)),
            'completed' => Tab::make(SessionStatus::Completed->getLabel())
                ->badge(TrainingSession::query()->where('status', SessionStatus::Completed->value)->count())
                ->badgeColor(SessionStatus::Completed->getColor())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', SessionStatus::Completed->value)),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            __('app.navigation.groups.training') => null,
            TrainingSessionResource::getUrl('index') => TrainingSessionResource::getNavigationLabel(),
        ];
    }
}
