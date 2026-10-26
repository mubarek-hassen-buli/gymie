<?php

declare(strict_types=1);

namespace App\Filament\Resources\WorkoutPrograms;

use App\Filament\Resources\WorkoutPrograms\Pages\CreateWorkoutProgram;
use App\Filament\Resources\WorkoutPrograms\Pages\EditWorkoutProgram;
use App\Filament\Resources\WorkoutPrograms\Pages\ListWorkoutPrograms;
use App\Filament\Resources\WorkoutPrograms\Schemas\WorkoutProgramForm;
use App\Filament\Resources\WorkoutPrograms\Tables\WorkoutProgramTable;
use App\Models\WorkoutProgram;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

/**
 * @extends Resource<WorkoutProgram>
 */
class WorkoutProgramResource extends Resource
{
    protected static ?string $model = WorkoutProgram::class;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getModelLabel(): string
    {
        return __('app.resources.workout_programs.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.resources.workout_programs.plural');
    }

    public static function getNavigationLabel(): string
    {
        return static::getPluralModelLabel();
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'title',
            'goal',
            'member.name',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return WorkoutProgramForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WorkoutProgramTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWorkoutPrograms::route('/'),
            'create' => CreateWorkoutProgram::route('/create'),
            'edit' => EditWorkoutProgram::route('/{record}/edit'),
        ];
    }
}
