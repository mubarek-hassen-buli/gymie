<?php

declare(strict_types=1);

namespace App\Filament\Resources\WorkoutPrograms\Pages;

use App\Filament\Resources\WorkoutPrograms\WorkoutProgramResource;
use App\Models\WorkoutProgram;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWorkoutPrograms extends ListRecords
{
    protected static string $resource = WorkoutProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label(__('app.actions.new', ['resource' => WorkoutProgramResource::getModelLabel()]))
                ->hidden(! WorkoutProgram::exists()),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            __('app.navigation.groups.training') => null,
            WorkoutProgramResource::getUrl('index') => WorkoutProgramResource::getNavigationLabel(),
        ];
    }
}
