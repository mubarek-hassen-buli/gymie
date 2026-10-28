<?php

declare(strict_types=1);

namespace App\Filament\Resources\WorkoutPrograms\Schemas;

use App\Enums\ProgramStatus;
use App\Models\Exercise;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class WorkoutProgramForm
{
    /**
     * Configure the workout program form schema.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Program Overview')
                    ->schema([
                        TextInput::make('title')
                            ->label(__('app.fields.name'))
                            ->placeholder('e.g. 12-Week Hypertrophy Routine')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),

                        Select::make('status')
                            ->label(__('app.fields.status'))
                            ->options(ProgramStatus::class)
                            ->default(ProgramStatus::Draft->value)
                            ->required()
                            ->enum(ProgramStatus::class)
                            ->columnSpan(1),

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
                            ->nullable()
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

                        DatePicker::make('start_date')
                            ->label(__('app.fields.start_date'))
                            ->columnSpan(1),

                        DatePicker::make('end_date')
                            ->label(__('app.fields.end_date'))
                            ->columnSpan(1),

                        TextInput::make('goal')
                            ->label(__('app.fields.goal'))
                            ->placeholder('e.g. Strength / Hypertrophy / Athletic Conditioning')
                            ->maxLength(255)
                            ->columnSpan(1),

                        Textarea::make('notes')
                            ->label(__('app.fields.note'))
                            ->placeholder('Warm-up rules, progression pacing, mobility work...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Section::make('Workout Routine & Exercise Prescription')
                    ->description('Organize daily exercises, sets, reps, target RPE, and recovery intervals.')
                    ->schema([
                        Repeater::make('exercises')
                            ->relationship('exercises')
                            ->orderColumn('sort_order')
                            ->defaultItems(0)
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->cloneable()
                            ->itemLabel(function (array $state): ?string {
                                $day = $state['day_label'] ?? 'Day';
                                $exerciseName = isset($state['exercise_id'])
                                    ? Exercise::find($state['exercise_id'])?->name
                                    : 'New Exercise';

                                return "{$day}: {$exerciseName}";
                            })
                            ->schema([
                                TextInput::make('day_label')
                                    ->label(__('app.fields.day_label'))
                                    ->placeholder('e.g. Day 1: Upper Push')
                                    ->required()
                                    ->columnSpan(3),

                                Select::make('exercise_id')
                                    ->label(__('app.resources.exercises.singular'))
                                    ->relationship('exercise', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpan(4),

                                TextInput::make('target_sets')
                                    ->label(__('app.fields.target_sets'))
                                    ->numeric()
                                    ->default(3)
                                    ->minValue(1)
                                    ->maxValue(50)
                                    ->required()
                                    ->columnSpan(1),

                                TextInput::make('target_reps')
                                    ->label(__('app.fields.target_reps'))
                                    ->placeholder('8-12')
                                    ->required()
                                    ->columnSpan(1),

                                TextInput::make('target_rpe')
                                    ->label(__('app.fields.target_rpe'))
                                    ->numeric()
                                    ->step(0.5)
                                    ->minValue(1)
                                    ->maxValue(10)
                                    ->placeholder('8.0')
                                    ->columnSpan(1),

                                TextInput::make('rest_seconds')
                                    ->label(__('app.fields.rest_seconds'))
                                    ->numeric()
                                    ->default(90)
                                    ->suffix('s')
                                    ->columnSpan(2),

                                TextInput::make('notes')
                                    ->label(__('app.fields.note'))
                                    ->placeholder('e.g. 2-second pause at bottom of stretch')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                            ])
                            ->columns(12)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
