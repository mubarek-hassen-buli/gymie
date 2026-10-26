<?php

declare(strict_types=1);

namespace App\Filament\Resources\TrainingSessions\Pages;

use App\Filament\Resources\TrainingSessions\TrainingSessionResource;
use App\Models\TrainingSession;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

/**
 * @property-read TrainingSession $record
 */
class EditTrainingSession extends EditRecord
{
    protected static string $resource = TrainingSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return __('app.actions.edit', [
            'resource' => TrainingSessionResource::getModelLabel(),
        ]);
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
