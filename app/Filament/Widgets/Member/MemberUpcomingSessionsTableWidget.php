<?php

declare(strict_types=1);

namespace App\Filament\Widgets\Member;

use App\Filament\Resources\TrainingSessions\TrainingSessionResource;
use App\Models\TrainingSession;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Schedule widget displaying upcoming coaching sessions for gym members.
 */
class MemberUpcomingSessionsTableWidget extends TableWidget
{
    protected static ?int $sort = -40;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->isMember();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->heading('My Training Sessions')
            ->description('Your upcoming and recent coaching sessions.')
            ->poll('30s')
            ->emptyStateHeading('No Coaching Sessions Yet')
            ->emptyStateDescription('You do not have any coaching sessions booked.')
            ->emptyStateIcon('heroicon-o-academic-cap')
            ->paginated(false)
            ->columns([
                TextColumn::make('scheduled_at')
                    ->label('Scheduled Time')
                    ->dateTime('M j, Y — g:i A')
                    ->sortable(),

                TextColumn::make('trainer.name')
                    ->label('Coach')
                    ->weight('bold'),

                TextColumn::make('duration_minutes')
                    ->label('Duration')
                    ->formatStateUsing(fn (int $state): string => "{$state} min"),

                TextColumn::make('status')
                    ->label(__('app.fields.status'))
                    ->badge(),

                TextColumn::make('trainer_notes')
                    ->label('Coach Notes')
                    ->placeholder('None')
                    ->limit(40),
            ])
            ->recordActions([
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
        $memberId = $user?->member?->id;

        if (! $memberId) {
            return TrainingSession::query()->whereRaw('1 = 0');
        }

        return TrainingSession::query()
            ->with(['trainer'])
            ->where('member_id', $memberId)
            ->orderBy('scheduled_at', 'desc')
            ->limit(10);
    }
}
