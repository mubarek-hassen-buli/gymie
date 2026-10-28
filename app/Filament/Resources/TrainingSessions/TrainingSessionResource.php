<?php

declare(strict_types=1);

namespace App\Filament\Resources\TrainingSessions;

use App\Filament\Resources\TrainingSessions\Pages\CreateTrainingSession;
use App\Filament\Resources\TrainingSessions\Pages\EditTrainingSession;
use App\Filament\Resources\TrainingSessions\Pages\ListTrainingSessions;
use App\Filament\Resources\TrainingSessions\Pages\ViewTrainingSession;
use App\Filament\Resources\TrainingSessions\RelationManagers\SessionWorkoutLogsRelationManager;
use App\Filament\Resources\TrainingSessions\Schemas\TrainingSessionForm;
use App\Filament\Resources\TrainingSessions\Tables\TrainingSessionTable;
use App\Models\TrainingSession;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends Resource<TrainingSession>
 */
class TrainingSessionResource extends Resource
{
    protected static ?string $model = TrainingSession::class;

    protected static ?string $recordTitleAttribute = 'id';

    public static function getModelLabel(): string
    {
        return __('app.resources.training_sessions.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.resources.training_sessions.plural');
    }

    public static function getNavigationLabel(): string
    {
        return static::getPluralModelLabel();
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'member.name',
            'trainer.name',
            'trainer_notes',
            'client_feedback',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return TrainingSessionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TrainingSessionTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            SessionWorkoutLogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTrainingSessions::route('/'),
            'create' => CreateTrainingSession::route('/create'),
            'view' => ViewTrainingSession::route('/{record}'),
            'edit' => EditTrainingSession::route('/{record}/edit'),
        ];
    }

    /**
     * Scope training session queries according to user role.
     *
     * @return Builder<TrainingSession>
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user) {
            return $query;
        }

        if ($user->isSuperAdmin() || $user->isFrontDesk()) {
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
