<?php

declare(strict_types=1);

namespace App\Filament\Resources\TrainingSessions\Tables;

use App\Enums\SessionStatus;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\PersonalTraining\SessionSchedulingService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TrainingSessionTable
{
    /**
     * Configure the training session table schema.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('scheduled_at')
                    ->label(__('app.fields.scheduled_at'))
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('member.name')
                    ->label(__('app.fields.member'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('trainer.name')
                    ->label(__('app.fields.trainer'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('duration_minutes')
                    ->label(__('app.fields.duration_minutes'))
                    ->suffix(' min')
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('app.fields.status'))
                    ->badge()
                    ->sortable(),

                TextColumn::make('workout_logs_count')
                    ->counts('workoutLogs')
                    ->label('Logged Sets')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('app.fields.created_at'))
                    ->date('d-m-Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('scheduled_at', 'desc')
            ->emptyStateIcon('heroicon-o-academic-cap')
            ->emptyStateHeading(__('app.empty.no_records', ['records' => __('app.resources.training_sessions.plural')]))
            ->emptyStateDescription(__('app.empty.create_to_get_started', ['resource' => __('app.resources.training_sessions.singular')]))
            ->emptyStateActions([
                CreateAction::make()
                    ->icon('heroicon-o-plus')
                    ->label(__('app.actions.new', ['resource' => __('app.resources.training_sessions.singular')])),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('app.fields.status'))
                    ->options(SessionStatus::class),

                SelectFilter::make('trainer_id')
                    ->label(__('app.fields.trainer'))
                    ->options(fn (): array => User::getTrainerOptions())
                    ->visible(fn (): bool => ! (bool) (auth()->user()?->isTrainer() && ! auth()->user()?->isSuperAdmin() && ! auth()->user()?->isFrontDesk()))
                    ->searchable(),

                SelectFilter::make('member_id')
                    ->label(__('app.fields.member'))
                    ->relationship('member', 'name', modifyQueryUsing: function ($query): void {
                        $user = auth()->user();
                        if ($user && $user->isTrainer() && ! $user->isSuperAdmin() && ! $user->isFrontDesk()) {
                            $query->whereHas('subscriptions', function ($sub) use ($user): void {
                                $sub->where('trainer_id', $user->id)
                                    ->where('status', 'ongoing');
                            });
                        }
                    })
                    ->visible(fn (): bool => ! (bool) auth()->user()?->isMember())
                    ->searchable(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('mark_completed')
                        ->label('Complete Session')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (TrainingSession $record): bool => $record->status === SessionStatus::Scheduled && ! (bool) auth()->user()?->isMember())
                        ->action(function (TrainingSession $record, SessionSchedulingService $service): void {
                            $service->completeSession($record);
                            Notification::make()
                                ->title('Session marked as completed')
                                ->success()
                                ->send();
                        }),

                    Action::make('mark_absent')
                        ->label('Mark Absent')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (TrainingSession $record): bool => $record->status === SessionStatus::Scheduled && ! (bool) auth()->user()?->isMember())
                        ->action(function (TrainingSession $record, SessionSchedulingService $service): void {
                            $service->markAbsent($record);
                            Notification::make()
                                ->title('Session marked as member absent')
                                ->warning()
                                ->send();
                        }),

                    ViewAction::make(),
                    EditAction::make()
                        ->visible(fn (): bool => ! (bool) auth()->user()?->isMember()),
                    DeleteAction::make()
                        ->visible(fn (): bool => ! (bool) auth()->user()?->isMember()),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => ! (bool) auth()->user()?->isMember()),
                ]),
            ]);
    }
}
