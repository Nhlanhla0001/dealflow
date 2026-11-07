<?php

namespace App\Enums;

enum NegotiationAction: string
{
    case Offer = 'offer';
    case Counteroffer = 'counteroffer';
    case Accept = 'accept';
    case Reject = 'reject';

    public function label(): string
    {
        return match ($this) {
            self::Offer => 'Offer',
            self::Counteroffer => 'Counteroffer',
            self::Accept => 'Accept',
            self::Reject => 'Reject',
        };
    }
}
