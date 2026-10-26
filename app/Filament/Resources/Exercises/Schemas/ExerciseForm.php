<?php

declare(strict_types=1);

namespace App\Filament\Resources\Exercises\Schemas;

use App\Enums\EquipmentType;
use App\Enums\MuscleGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ExerciseForm
{
    /**
     * Configure the exercise form schema.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Exercise Details')
                    ->schema([
                        TextInput::make('name')
                            ->label(__('app.fields.name'))
                            ->placeholder('e.g. Barbell Bench Press')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),

                        Toggle::make('is_active')
                            ->label(__('app.status.active'))
                            ->default(true)
                            ->required()
                            ->columnSpan(1),

                        Select::make('muscle_group')
                            ->label(__('app.fields.muscle_group'))
                            ->options(MuscleGroup::class)
                            ->enum(MuscleGroup::class)
                            ->required()
                            ->searchable()
                            ->columnSpan(1),

                        Select::make('equipment_type')
                            ->label(__('app.fields.equipment_type'))
                            ->options(EquipmentType::class)
                            ->enum(EquipmentType::class)
                            ->required()
                            ->searchable()
                            ->columnSpan(1),

                        TextInput::make('video_url')
                            ->label(__('app.fields.video_url'))
                            ->url()
                            ->maxLength(500)
                            ->placeholder('https://www.youtube.com/watch?v=...')
                            ->columnSpan(1),

                        Textarea::make('description')
                            ->label(__('app.fields.description'))
                            ->placeholder('Brief overview or target cues...')
                            ->maxLength(500)
                            ->rows(2)
                            ->columnSpanFull(),

                        Textarea::make('instructions')
                            ->label(__('app.fields.instructions'))
                            ->placeholder('Step-by-step execution form cues, breathing, setup...')
                            ->maxLength(2000)
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
            ]);
    }
}
