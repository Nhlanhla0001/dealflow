<?php

namespace App\Enums;

enum AgreementStatus: string
{
    case AwaitingApprovals = 'awaiting_approvals';
    case Approved = 'approved';
    case Superseded = 'superseded';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::AwaitingApprovals => 'Awaiting approvals',
            self::Approved => 'Approved',
            self::Superseded => 'Superseded',
            self::Cancelled => 'Cancelled',
        };
    }
}
