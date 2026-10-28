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
use Illuminate\Database\Eloquent\Builder;

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

    /**
     * Scope workout program queries according to user role.
     *
     * @return Builder<WorkoutProgram>
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user) {
            return $query;
        }

        if ($user->isSuperAdmin()) {
            return $query;
        }

        if ($user->isTrainer()) {
            return $query->where('trainer_id', $user->id);
        }

        if ($user->isMember()) {
            $memberId = $user->member?->id;

            return $memberId
                ? $query->where('member_id', $memberId)
                : $query->whereRaw('1 = 0');
        }

        return $query;
    }
}
