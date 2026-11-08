<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Created = 'created';
    case Captured = 'captured';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Created => 'Created',
            self::Captured => 'Captured',
            self::Failed => 'Failed',
            self::Cancelled => 'Cancelled',
        };
    }
}
