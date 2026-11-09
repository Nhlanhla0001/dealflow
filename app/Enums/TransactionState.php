<?php

namespace App\Enums;

enum TransactionState: string
{
    case Draft = 'draft';
    case Negotiating = 'negotiating';
    case AgreementProposed = 'agreement_proposed';
    case AwaitingApprovals = 'awaiting_approvals';
    case Approved = 'approved';
    case PaymentPending = 'payment_pending';
    case Paid = 'paid';
    case Cancelled = 'cancelled';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Negotiating => 'Negotiating',
            self::AgreementProposed => 'Agreement proposed',
            self::AwaitingApprovals => 'Awaiting approvals',
            self::Approved => 'Approved',
            self::PaymentPending => 'Payment pending',
            self::Paid => 'Paid',
            self::Cancelled => 'Cancelled',
            self::Failed => 'Failed',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Paid, self::Cancelled, self::Failed], true);
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }

    /**
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Negotiating, self::Cancelled],
            self::Negotiating => [self::AgreementProposed, self::Failed, self::Cancelled],
            self::AgreementProposed => [self::AwaitingApprovals, self::Cancelled],
            self::AwaitingApprovals => [self::Approved, self::Failed, self::Cancelled],
            self::Approved => [self::PaymentPending, self::AwaitingApprovals, self::Cancelled],
            self::PaymentPending => [self::Paid, self::Failed, self::Cancelled],
            self::Paid, self::Cancelled, self::Failed => [],
        };
    }
}
