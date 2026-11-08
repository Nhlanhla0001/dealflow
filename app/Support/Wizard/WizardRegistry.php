<?php

namespace App\Support\Wizard;

use InvalidArgumentException;

class WizardRegistry
{
    public function __construct(
        private readonly SellerListingWizard $sellerListing,
        private readonly BuyerRequestWizard $buyerRequest,
        private readonly AgentConfigurationWizard $agentConfiguration,
        private readonly OnboardingWizard $onboarding,
    ) {}

    public function for(string $type): WizardDefinition
    {
        return match ($type) {
            'seller_listing' => $this->sellerListing,
            'buyer_request' => $this->buyerRequest,
            'agent_config' => $this->agentConfiguration,
            'onboarding' => $this->onboarding,
            default => throw new InvalidArgumentException("Unknown wizard type [{$type}]."),
        };
    }
}
