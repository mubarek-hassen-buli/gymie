<?php

declare(strict_types=1);

namespace App\Filament\Resources\TrainingSessions\RelationManagers;

use App\Models\Exercise;
use App\Models\SessionWorkoutLog;
use App\Models\TrainingSession;
use App\Services\PersonalTraining\PersonalBestTrackerService;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SessionWorkoutLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'workoutLogs';

    protected static ?string $title = 'Workout Exercise Logs';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(6)
            ->components([
                Select::make('exercise_id')
                    ->label(__('app.resources.exercises.singular'))
                    ->relationship('exercise', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpan(2),

                TextInput::make('set_number')
                    ->label('Set #')
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->required()
                    ->columnSpan(1),

                TextInput::make('weight_kg')
                    ->label(__('app.fields.weight_kg'))
                    ->numeric()
                    ->step(0.25)
                    ->minValue(0)
                    ->required()
                    ->columnSpan(1),

                TextInput::make('reps_completed')
                    ->label(__('app.fields.reps_completed'))
                    ->numeric()
                    ->minValue(1)
                    ->required()
                    ->columnSpan(1),

                TextInput::make('rpe')
                    ->label(__('app.fields.target_rpe'))
                    ->numeric()
                    ->step(0.5)
                    ->minValue(1)
                    ->maxValue(10)
                    ->placeholder('8.5')
                    ->columnSpan(1),

                TextInput::make('notes')
                    ->label(__('app.fields.note'))
                    ->placeholder('Reps in reserve, tempo, spotter assisted...')
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('exercise.name')
                    ->label(__('app.resources.exercises.singular'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('set_number')
                    ->label('Set #')
                    ->sortable(),

                TextColumn::make('weight_kg')
                    ->label(__('app.fields.weight_kg'))
                    ->suffix(' kg')
                    ->sortable(),

                TextColumn::make('reps_completed')
                    ->label(__('app.fields.reps_completed'))
                    ->sortable(),

                TextColumn::make('rpe')
                    ->label('RPE')
                    ->badge()
                    ->color('warning')
                    ->placeholder('-'),

                IconColumn::make('is_pr')
                    ->label('PR ⭐')
                    ->boolean()
                    ->trueIcon('heroicon-s-trophy')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('warning')
                    ->falseColor('gray'),

                TextColumn::make('notes')
                    ->label(__('app.fields.note'))
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('set_number', 'asc')
            ->headerActions([
                CreateAction::make()
                    ->label('Log Exercise Set')
                    ->icon('heroicon-o-plus')
                    ->using(function (array $data): Model {
                        /** @var TrainingSession $owner */
                        $owner = $this->getOwnerRecord();
                        $exercise = Exercise::findOrFail($data['exercise_id']);

                        $service = app(PersonalBestTrackerService::class);
                        $log = $service->logSet(
                            session: $owner,
                            exercise: $exercise,
                            setNumber: (int) $data['set_number'],
                            weightKg: (float) $data['weight_kg'],
                            reps: (int) $data['reps_completed'],
                            rpe: isset($data['rpe']) && $data['rpe'] !== '' ? (float) $data['rpe'] : null,
                            notes: $data['notes'] ?? null,
                        );

                        if ($log->is_pr) {
                            Notification::make()
                                ->title('Personal Record (PR) Achieved! 🏆')
                                ->body("{$exercise->name}: {$log->weight_kg} kg for {$log->reps_completed} reps is a new personal best!")
                                ->success()
                                ->send();
                        }

                        return $log;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
