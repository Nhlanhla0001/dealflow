<?php

namespace App\Enums;

enum NegotiationStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Agreed = 'agreed';
    case Deadlocked = 'deadlocked';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Active => 'Active',
            self::Agreed => 'Agreed',
            self::Deadlocked => 'Deadlocked',
            self::Cancelled => 'Cancelled',
        };
    }
}
