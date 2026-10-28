<?php

declare(strict_types=1);

namespace App\Filament\Resources\Members\RelationManagers;

use App\Filament\Resources\TrainingSessions\TrainingSessionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TrainingSessionsRelationManager extends RelationManager
{
    protected static string $relationship = 'trainingSessions';

    protected static ?string $title = null;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('app.resources.training_sessions.plural');
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return TrainingSessionResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return TrainingSessionResource::table($table)
            ->modifyQueryUsing(function (Builder $query): void {
                $user = auth()->user();
                if ($user && $user->isTrainer() && ! $user->isSuperAdmin() && ! $user->isFrontDesk()) {
                    $query->where('trainer_id', $user->id);
                }
            })
            ->headerActions([
                CreateAction::make()
                    ->icon('heroicon-s-plus')
                    ->modalHeading(__('app.actions.new', ['resource' => __('app.resources.training_sessions.singular')]))
                    ->modalWidth('4xl')
                    ->closeModalByClickingAway(false)
                    ->createAnother(false),
            ]);
    }
}
