<?php

namespace App\Enums;

enum OfferStatus: string
{
    case Active = 'active';
    case Accepted = 'accepted';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Accepted => 'Accepted',
            self::Rejected => 'Rejected',
        };
    }
}
