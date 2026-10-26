<?php

declare(strict_types=1);

namespace App\Filament\Resources\Exercises\Tables;

use App\Enums\EquipmentType;
use App\Enums\MuscleGroup;
use App\Models\Exercise;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ExerciseTable
{
    /**
     * Configure the exercise table schema.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('name')
                    ->label(__('app.fields.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('muscle_group')
                    ->label(__('app.fields.muscle_group'))
                    ->badge()
                    ->sortable()
                    ->searchable(),

                TextColumn::make('equipment_type')
                    ->label(__('app.fields.equipment_type'))
                    ->badge()
                    ->sortable()
                    ->searchable(),

                IconColumn::make('is_active')
                    ->label(__('app.status.active'))
                    ->boolean()
                    ->sortable(),

                TextColumn::make('video_url')
                    ->label(__('app.fields.video_url'))
                    ->formatStateUsing(fn (?string $state): string => $state ? 'Link' : '-')
                    ->url(fn (Exercise $record): ?string => $record->video_url, shouldOpenInNewTab: true)
                    ->color('primary')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('app.fields.created_at'))
                    ->date('d-m-Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->emptyStateIcon('heroicon-o-fire')
            ->emptyStateHeading(__('app.empty.no_records', ['records' => __('app.resources.exercises.plural')]))
            ->emptyStateDescription(__('app.empty.create_to_get_started', ['resource' => __('app.resources.exercises.singular')]))
            ->emptyStateActions([
                CreateAction::make()
                    ->icon('heroicon-o-plus')
                    ->label(__('app.actions.new', ['resource' => __('app.resources.exercises.singular')])),
            ])
            ->filters([
                SelectFilter::make('muscle_group')
                    ->label(__('app.fields.muscle_group'))
                    ->options(MuscleGroup::class),

                SelectFilter::make('equipment_type')
                    ->label(__('app.fields.equipment_type'))
                    ->options(EquipmentType::class),

                TernaryFilter::make('is_active')
                    ->label(__('app.status.active')),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                    RestoreAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
