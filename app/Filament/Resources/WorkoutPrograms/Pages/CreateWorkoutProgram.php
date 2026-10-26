<?php

declare(strict_types=1);

namespace App\Filament\Resources\WorkoutPrograms\Pages;

use App\Filament\Resources\WorkoutPrograms\WorkoutProgramResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWorkoutProgram extends CreateRecord
{
    protected static string $resource = WorkoutProgramResource::class;

    protected static bool $canCreateAnother = false;

    public function getTitle(): string
    {
        return __('app.actions.new', ['resource' => WorkoutProgramResource::getModelLabel()]);
    }

    public function getBreadcrumbs(): array
    {
        return [
            __('app.navigation.groups.training') => null,
            WorkoutProgramResource::getUrl('index') => WorkoutProgramResource::getNavigationLabel(),
            __('app.actions.new', ['resource' => WorkoutProgramResource::getModelLabel()]),
        ];
    }
}
