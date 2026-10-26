<?php

declare(strict_types=1);

namespace App\Filament\Resources\WorkoutPrograms\Tables;

use App\Enums\ProgramStatus;
use App\Models\User;
use App\Models\WorkoutProgram;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WorkoutProgramTable
{
    /**
     * Configure the workout program table schema.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('title')
                    ->label(__('app.fields.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('member.name')
                    ->label(__('app.fields.member'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('trainer.name')
                    ->label(__('app.fields.trainer'))
                    ->searchable()
                    ->sortable()
                    ->placeholder('None'),

                TextColumn::make('status')
                    ->label(__('app.fields.status'))
                    ->badge()
                    ->sortable(),

                TextColumn::make('goal')
                    ->label(__('app.fields.goal'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('exercises_count')
                    ->counts('exercises')
                    ->label('Exercises')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('start_date')
                    ->label(__('app.fields.start_date'))
                    ->date('d-m-Y')
                    ->sortable(),

                TextColumn::make('end_date')
                    ->label(__('app.fields.end_date'))
                    ->date('d-m-Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('app.fields.created_at'))
                    ->date('d-m-Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->emptyStateIcon('heroicon-o-clipboard-document-check')
            ->emptyStateHeading(__('app.empty.no_records', ['records' => __('app.resources.workout_programs.plural')]))
            ->emptyStateDescription(__('app.empty.create_to_get_started', ['resource' => __('app.resources.workout_programs.singular')]))
            ->emptyStateActions([
                CreateAction::make()
                    ->icon('heroicon-o-plus')
                    ->label(__('app.actions.new', ['resource' => __('app.resources.workout_programs.singular')])),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('app.fields.status'))
                    ->options(ProgramStatus::class),

                SelectFilter::make('trainer_id')
                    ->label(__('app.fields.trainer'))
                    ->options(fn (): array => User::getTrainerOptions())
                    ->searchable(),

                SelectFilter::make('member_id')
                    ->label(__('app.fields.member'))
                    ->relationship('member', 'name')
                    ->searchable(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
