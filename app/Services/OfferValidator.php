<?php

namespace App\Services;

use InvalidArgumentException;

class OfferValidator
{
    public function assertBuyerProposal(int $priceMinor, int $buyerMaxMinor): void
    {
        if ($priceMinor < 1 || $priceMinor > $buyerMaxMinor) {
            throw new InvalidArgumentException('The buyer proposal must be inside the approved budget.');
        }
    }

    public function assertSellerProposal(int $priceMinor, int $sellerFloorMinor): void
    {
        if ($priceMinor < $sellerFloorMinor) {
            throw new InvalidArgumentException('The seller proposal cannot go below the approved floor.');
        }
    }

    public function assertDeliveryDays(int $days): void
    {
        if ($days < 1 || $days > 365) {
            throw new InvalidArgumentException('Delivery days must be between 1 and 365.');
        }
    }
}
