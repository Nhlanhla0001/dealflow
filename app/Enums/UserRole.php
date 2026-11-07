<?php

namespace App\Enums;

enum UserRole: string
{
    case Buyer = 'buyer';
    case Seller = 'seller';
    case Both = 'both';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Buyer => 'Buyer',
            self::Seller => 'Seller',
            self::Both => 'Buyer and seller',
            self::Admin => 'Administrator',
        };
    }

    public function canBuy(): bool
    {
        return in_array($this, [self::Buyer, self::Both, self::Admin], true);
    }

    public function canSell(): bool
    {
        return in_array($this, [self::Seller, self::Both, self::Admin], true);
    }
}
