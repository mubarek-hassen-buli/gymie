<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum MuscleGroup: string implements HasColor, HasLabel
{
    case Chest = 'chest';
    case Back = 'back';
    case Legs = 'legs';
    case Shoulders = 'shoulders';
    case Arms = 'arms';
    case Core = 'core';
    case Cardio = 'cardio';
    case FullBody = 'full_body';

    public function getLabel(): string
    {
        return match ($this) {
            self::Chest => 'Chest',
            self::Back => 'Back',
            self::Legs => 'Legs',
            self::Shoulders => 'Shoulders',
            self::Arms => 'Arms',
            self::Core => 'Core',
            self::Cardio => 'Cardio',
            self::FullBody => 'Full Body',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Chest => 'info',
            self::Back => 'primary',
            self::Legs => 'success',
            self::Shoulders => 'warning',
            self::Arms => 'danger',
            self::Core => 'gray',
            self::Cardio => 'danger',
            self::FullBody => 'primary',
        };
    }
}
