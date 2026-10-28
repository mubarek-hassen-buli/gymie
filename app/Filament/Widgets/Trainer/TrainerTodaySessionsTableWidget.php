<?php

declare(strict_types=1);

namespace App\Filament\Widgets\Trainer;

use App\Enums\SessionStatus;
use App\Filament\Resources\TrainingSessions\TrainingSessionResource;
use App\Models\TrainingSession;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Schedule widget displaying today's sessions for the authenticated trainer.
 */
class TrainerTodaySessionsTableWidget extends TableWidget
{
    protected static ?int $sort = -40;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->isTrainer();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->heading("Today's Coaching Schedule")
            ->description('Your scheduled personal training appointments for today.')
            ->poll('30s')
            ->emptyStateHeading('No Sessions Scheduled For Today')
            ->emptyStateDescription('You have no client appointments booked for today.')
            ->emptyStateIcon('heroicon-o-calendar-days')
            ->paginated(false)
            ->columns([
                TextColumn::make('scheduled_at')
                    ->label('Time')
                    ->time('g:i A')
                    ->sortable(),

                TextColumn::make('member.name')
                    ->label('Client')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('duration_minutes')
                    ->label('Duration')
                    ->formatStateUsing(fn (int $state): string => "{$state} min"),

                TextColumn::make('status')
                    ->label(__('app.fields.status'))
                    ->badge(),

                TextColumn::make('trainer_notes')
                    ->label('Focus / Notes')
                    ->placeholder('No notes')
                    ->limit(40),
            ])
            ->recordActions([
                Action::make('mark_completed')
                    ->label('Complete')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (TrainingSession $record): bool => in_array($record->status?->value ?? (string) $record->status, [
                        SessionStatus::Scheduled->value,
                        SessionStatus::InProgress->value,
                    ], true))
                    ->action(function (TrainingSession $record): void {
                        $record->update(['status' => SessionStatus::Completed]);
                        Notification::make()
                            ->title('Session Completed')
                            ->body("Marked session with {$record->member?->name} as completed.")
                            ->success()
                            ->send();
                    }),

                Action::make('mark_absent')
                    ->label('Absent')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (TrainingSession $record): bool => in_array($record->status?->value ?? (string) $record->status, [
                        SessionStatus::Scheduled->value,
                    ], true))
                    ->action(function (TrainingSession $record): void {
                        $record->update(['status' => SessionStatus::Absent]);
                        Notification::make()
                            ->title('Member Marked Absent')
                            ->body("Marked {$record->member?->name} as absent.")
                            ->warning()
                            ->send();
                    }),

                ViewAction::make()
                    ->url(fn (TrainingSession $record): string => TrainingSessionResource::getUrl('view', ['record' => $record])),
            ]);
    }

    /**
     * @return Builder<TrainingSession>
     */
    protected function getTableQuery(): Builder
    {
        $user = auth()->user();

        if (! $user) {
            return TrainingSession::query()->whereRaw('1 = 0');
        }

        return TrainingSession::query()
            ->with(['member'])
            ->where('trainer_id', $user->id)
            ->whereDate('scheduled_at', Carbon::today())
            ->orderBy('scheduled_at', 'asc');
    }
}
