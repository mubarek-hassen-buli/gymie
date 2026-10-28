<?php

declare(strict_types=1);

namespace App\Filament\Resources\TrainingSessions\Schemas;

use App\Enums\SessionStatus;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class TrainingSessionForm
{
    /**
     * Configure the training session form schema.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Session Information')
                    ->schema([
                        Select::make('member_id')
                            ->label(__('app.fields.member'))
                            ->relationship('member', 'name', modifyQueryUsing: function (Builder $query): void {
                                $user = auth()->user();
                                if ($user && $user->isTrainer() && ! $user->isSuperAdmin() && ! $user->isFrontDesk()) {
                                    $query->whereHas('subscriptions', function (Builder $sub) use ($user): void {
                                        $sub->where('trainer_id', $user->id)
                                            ->where('status', 'ongoing');
                                    });
                                }
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->columnSpan(1),

                        Select::make('trainer_id')
                            ->label(__('app.fields.trainer'))
                            ->options(fn (): array => User::getTrainerOptions())
                            ->default(fn (): ?int => (auth()->user()?->isTrainer() && ! auth()->user()?->isSuperAdmin() && ! auth()->user()?->isFrontDesk()) ? auth()->id() : null)
                            ->disabled(fn (): bool => (bool) (auth()->user()?->isTrainer() && ! auth()->user()?->isSuperAdmin() && ! auth()->user()?->isFrontDesk()))
                            ->dehydrated()
                            ->searchable()
                            ->required()
                            ->columnSpan(1),

                        Select::make('subscription_id')
                            ->label(__('app.fields.subscription'))
                            ->relationship('subscription', 'id', modifyQueryUsing: function (Builder $query, Get $get): void {
                                if ($memberId = $get('member_id')) {
                                    $query->where('member_id', $memberId);
                                }
                            })
                            ->searchable()
                            ->nullable()
                            ->columnSpan(1),

                        DateTimePicker::make('scheduled_at')
                            ->label(__('app.fields.scheduled_at'))
                            ->required()
                            ->default(now()->addHour()->startOfHour())
                            ->columnSpan(1),

                        TextInput::make('duration_minutes')
                            ->label(__('app.fields.duration_minutes'))
                            ->numeric()
                            ->default(60)
                            ->minValue(15)
                            ->maxValue(240)
                            ->suffix('min')
                            ->required()
                            ->columnSpan(1),

                        Select::make('status')
                            ->label(__('app.fields.status'))
                            ->options(SessionStatus::class)
                            ->default(SessionStatus::Scheduled->value)
                            ->required()
                            ->enum(SessionStatus::class)
                            ->live()
                            ->columnSpan(1),

                        TextInput::make('cancellation_reason')
                            ->label(__('app.fields.cancellation_reason'))
                            ->placeholder('Reason for cancelling the session...')
                            ->maxLength(255)
                            ->visible(fn (Get $get): bool => in_array($get('status'), [
                                SessionStatus::CancelledByMember->value,
                                SessionStatus::CancelledByTrainer->value,
                            ], true))
                            ->columnSpanFull(),

                        Textarea::make('trainer_notes')
                            ->label(__('app.fields.trainer_notes'))
                            ->placeholder('Observations on form, energy level, volume adjustments...')
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('client_feedback')
                            ->label(__('app.fields.client_feedback'))
                            ->placeholder('Client comments, soreness, motivation notes...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
            ]);
    }
}
