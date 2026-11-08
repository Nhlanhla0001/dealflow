<?php

namespace App\Enums;

enum WizardType: string
{
    case SellerListing = 'seller_listing';
    case BuyerRequest = 'buyer_request';
    case AgentConfig = 'agent_config';
    case Onboarding = 'onboarding';
}
