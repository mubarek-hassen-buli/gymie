<?php

declare(strict_types=1);

namespace App\Filament\Resources\Exercises\Pages;

use App\Filament\Resources\Exercises\ExerciseResource;
use App\Models\Exercise;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

/**
 * @property-read Exercise $record
 */
class EditExercise extends EditRecord
{
    protected static string $resource = ExerciseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return __('app.actions.edit', [
            'resource' => ExerciseResource::getModelLabel(),
        ]);
    }

    public function getBreadcrumbs(): array
    {
        return [
            __('app.navigation.groups.training') => null,
            ExerciseResource::getUrl('index') => ExerciseResource::getNavigationLabel(),
            $this->record->name,
        ];
    }
}
