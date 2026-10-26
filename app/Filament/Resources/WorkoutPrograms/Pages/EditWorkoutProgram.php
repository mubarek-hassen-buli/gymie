<?php

declare(strict_types=1);

namespace App\Filament\Resources\WorkoutPrograms\Pages;

use App\Filament\Resources\WorkoutPrograms\WorkoutProgramResource;
use App\Models\WorkoutProgram;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * @property-read WorkoutProgram $record
 */
class EditWorkoutProgram extends EditRecord
{
    protected static string $resource = WorkoutProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return __('app.actions.edit', [
            'resource' => WorkoutProgramResource::getModelLabel(),
        ]);
    }

    public function getBreadcrumbs(): array
    {
        return [
            __('app.navigation.groups.training') => null,
            WorkoutProgramResource::getUrl('index') => WorkoutProgramResource::getNavigationLabel(),
            $this->record->title,
        ];
    }
}
