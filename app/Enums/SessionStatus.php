<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum SessionStatus: string implements HasColor, HasIcon, HasLabel
{
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case MemberAbsent = 'member_absent';
    case CancelledByMember = 'cancelled_by_member';
    case CancelledByTrainer = 'cancelled_by_trainer';

    public function getLabel(): string
    {
        return match ($this) {
            self::Scheduled => 'Scheduled',
            self::Completed => 'Completed',
            self::MemberAbsent => 'Member Absent (No-Show)',
            self::CancelledByMember => 'Cancelled by Member',
            self::CancelledByTrainer => 'Cancelled by Trainer',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Scheduled => 'info',
            self::Completed => 'success',
            self::MemberAbsent => 'danger',
            self::CancelledByMember => 'warning',
            self::CancelledByTrainer => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Scheduled => 'heroicon-m-calendar',
            self::Completed => 'heroicon-m-check-circle',
            self::MemberAbsent => 'heroicon-m-x-circle',
            self::CancelledByMember => 'heroicon-m-arrow-uturn-left',
            self::CancelledByTrainer => 'heroicon-m-no-symbol',
        };
    }

    public function isFinal(): bool
    {
        return $this !== self::Scheduled;
    }
}
