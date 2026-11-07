import type { TransactionState } from '@/types';

export type BadgeVariant = 'default' | 'secondary' | 'outline' | 'destructive';

export const stateMeta: Record<
    TransactionState,
    { label: string; variant: BadgeVariant }
> = {
    draft: { label: 'Draft', variant: 'outline' },
    negotiating: { label: 'Negotiating', variant: 'default' },
    agreement_proposed: {
        label: 'Agreement proposed',
        variant: 'secondary',
    },
    awaiting_approvals: { label: 'Awaiting approvals', variant: 'secondary' },
    approved: { label: 'Approved', variant: 'default' },
    payment_pending: { label: 'Payment pending', variant: 'secondary' },
    paid: { label: 'Paid', variant: 'default' },
    cancelled: { label: 'Cancelled', variant: 'outline' },
    failed: { label: 'Failed', variant: 'destructive' },
};

export function formatMoney(minor: number, currency: string): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency,
        maximumFractionDigits: 0,
    }).format(minor / 100);
}

export function formatDate(value: string): string {
    return new Date(value).toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}
