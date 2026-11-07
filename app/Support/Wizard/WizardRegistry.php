<?php

namespace App\Support\Wizard;

use InvalidArgumentException;

class WizardRegistry
{
    public function __construct(private readonly SellerListingWizard $sellerListing) {}

    public function for(string $type): WizardDefinition
    {
        return match ($type) {
            'seller_listing' => $this->sellerListing,
            default => throw new InvalidArgumentException("Unknown wizard type [{$type}]."),
        };
    }
}
