<?php

namespace App\Enums;

enum PricingType: string
{
    case Fixed = 'fixed';
    case Hourly = 'hourly';
    case StartingAt = 'starting_at';

    public function label(): string
    {
        return match ($this) {
            self::Fixed => 'Fixed price',
            self::Hourly => 'Hourly',
            self::StartingAt => 'Starting at',
        };
    }
}
