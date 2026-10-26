<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EquipmentType: string implements HasColor, HasLabel
{
    case Barbell = 'barbell';
    case Dumbbell = 'dumbbell';
    case Machine = 'machine';
    case Cable = 'cable';
    case Bodyweight = 'bodyweight';
    case Kettlebell = 'kettlebell';
    case ResistanceBand = 'resistance_band';
    case Cardio = 'cardio';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Barbell => 'Barbell',
            self::Dumbbell => 'Dumbbell',
            self::Machine => 'Machine',
            self::Cable => 'Cable',
            self::Bodyweight => 'Bodyweight',
            self::Kettlebell => 'Kettlebell',
            self::ResistanceBand => 'Resistance Band',
            self::Cardio => 'Cardio Machine',
            self::Other => 'Other / None',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Barbell => 'danger',
            self::Dumbbell => 'primary',
            self::Machine => 'info',
            self::Cable => 'warning',
            self::Bodyweight => 'success',
            self::Kettlebell => 'danger',
            self::ResistanceBand => 'gray',
            self::Cardio => 'warning',
            self::Other => 'gray',
        };
    }
}
