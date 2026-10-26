<?php

declare(strict_types=1);

namespace App\Filament\Resources\TrainingSessions\Pages;

use App\Filament\Resources\TrainingSessions\TrainingSessionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTrainingSession extends CreateRecord
{
    protected static string $resource = TrainingSessionResource::class;

    protected static bool $canCreateAnother = false;

    public function getTitle(): string
    {
        return __('app.actions.new', ['resource' => TrainingSessionResource::getModelLabel()]);
    }

    public function getBreadcrumbs(): array
    {
        return [
            __('app.navigation.groups.training') => null,
            TrainingSessionResource::getUrl('index') => TrainingSessionResource::getNavigationLabel(),
            __('app.actions.new', ['resource' => TrainingSessionResource::getModelLabel()]),
        ];
    }
}
