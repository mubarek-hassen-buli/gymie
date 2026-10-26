<?php

declare(strict_types=1);

namespace App\Filament\Resources\Exercises\Pages;

use App\Filament\Resources\Exercises\ExerciseResource;
use App\Models\Exercise;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExercises extends ListRecords
{
    protected static string $resource = ExerciseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label(__('app.actions.new', ['resource' => ExerciseResource::getModelLabel()]))
                ->hidden(! Exercise::exists()),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            __('app.navigation.groups.training') => null,
            ExerciseResource::getUrl('index') => ExerciseResource::getNavigationLabel(),
        ];
    }
}
