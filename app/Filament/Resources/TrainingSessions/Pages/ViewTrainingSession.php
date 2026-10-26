<?php

declare(strict_types=1);

namespace App\Filament\Resources\TrainingSessions\Pages;

use App\Filament\Resources\TrainingSessions\TrainingSessionResource;
use App\Models\TrainingSession;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

/**
 * @property-read TrainingSession $record
 */
class ViewTrainingSession extends ViewRecord
{
    protected static string $resource = TrainingSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return "Training Session #{$this->record->id} ({$this->record->member->name})";
    }

    public function getBreadcrumbs(): array
    {
        return [
            __('app.navigation.groups.training') => null,
            TrainingSessionResource::getUrl('index') => TrainingSessionResource::getNavigationLabel(),
            "Session #{$this->record->id}",
        ];
    }
}
