import { Head, Link } from '@inertiajs/react';
import {
    CheckCircle2,
    CreditCard,
    ExternalLink,
    ShieldCheck,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { WizardProgress } from '@/components/wizard/wizard-progress';
import { formatMoney } from '@/lib/format';
import type { DealAgreement, DealPaymentOrder, DealTransaction } from '@/types';

type Order = {
    id: number;
    status: string;
    amount_minor: number;
    currency: string;
    paid_at: string | null;
};

type Step = {
    key: string;
    label: string;
    description: string;
};

type Props = {
    transaction: DealTransaction;
    agreement: DealAgreement | null;
    payment: DealPaymentOrder | null;
    order: Order | null;
    canPay: boolean;
    steps: Step[];
    currentStep: string;
    completedSteps: string[];
};

export default function CheckoutShow({
    transaction,
    agreement,
    payment,
    order,
    canPay,
    steps,
    currentStep,
    completedSteps,
}: Props) {
    const step = steps.find((item) => item.key === currentStep) ?? steps[0];
    const index = steps.findIndex((item) => item.key === currentStep);
    const amount =
        agreement?.price_minor ??
        transaction.latest_agreement?.price_minor ??
        transaction.offer?.price_minor ??
        transaction.budget_minor;
    const currency =
        agreement?.currency ??
        transaction.latest_agreement?.currency ??
        transaction.currency;
    const csrfToken =
        typeof document !== 'undefined'
            ? (document
                  .querySelector('meta[name="csrf-token"]')
                  ?.getAttribute('content') ?? '')
            : '';

    const progressSteps = steps.map((item) => ({
        ...item,
        review: false,
    }));

    return (
        <>
            <Head title="Checkout" />

            <div className="mx-auto w-full max-w-4xl">
                <div className="mb-6">
                    <p className="text-xs font-medium tracking-[0.22em] text-muted-foreground uppercase">
                        Checkout
                    </p>
                    <h1 className="mt-2 text-2xl font-semibold tracking-tight">
                        {transaction.title}
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {transaction.buyer?.name} → {transaction.seller?.name} ·{' '}
                        {formatMoney(amount, currency)}
                    </p>
                </div>

                <WizardProgress
                    steps={progressSteps}
                    currentStep={currentStep}
                    completedSteps={completedSteps}
                />

                <div className="mt-8 rounded-xl border border-border bg-card p-5 sm:p-6">
                    <p className="font-mono text-xs text-muted-foreground">
                        Step {index + 1} of {steps.length}
                    </p>
                    <h2 className="mt-2 text-xl font-semibold tracking-tight">
                        {step?.label}
                    </h2>
                    <p className="mt-1 text-sm leading-6 text-muted-foreground">
                        {step?.description}
                    </p>

                    <div className="mt-6">
                        {currentStep === 'agreement' && (
                            <div className="space-y-3 text-sm">
                                <div className="flex items-center justify-between gap-4">
                                    <span className="text-muted-foreground">
                                        Agreement status
                                    </span>
                                    <Badge variant="outline">
                                        {agreement?.status ?? 'not approved'}
                                    </Badge>
                                </div>
                                <div className="flex items-center justify-between gap-4">
                                    <span className="text-muted-foreground">
                                        Price
                                    </span>
                                    <span className="font-medium">
                                        {formatMoney(amount, currency)}
                                    </span>
                                </div>
                                <div className="flex items-center justify-between gap-4">
                                    <span className="text-muted-foreground">
                                        Delivery
                                    </span>
                                    <span className="font-medium">
                                        {agreement?.delivery_days ??
                                            transaction.delivery_days}{' '}
                                        days
                                    </span>
                                </div>
                            </div>
                        )}

                        {currentStep === 'amount' && (
                            <div className="space-y-3 text-sm">
                                <div className="flex items-center justify-between gap-4">
                                    <span className="text-muted-foreground">
                                        Agreement amount
                                    </span>
                                    <span className="font-medium">
                                        {formatMoney(amount, currency)}
                                    </span>
                                </div>
                                <div className="flex items-center justify-between gap-4">
                                    <span className="text-muted-foreground">
                                        Platform fee
                                    </span>
                                    <span className="font-medium">
                                        {formatMoney(0, currency)}
                                    </span>
                                </div>
                                <div className="flex items-center justify-between gap-4 border-t border-border pt-3">
                                    <span className="font-medium">Total</span>
                                    <span className="text-lg font-semibold">
                                        {formatMoney(amount, currency)}
                                    </span>
                                </div>
                            </div>
                        )}

                        {currentStep === 'method' && (
                            <div className="space-y-4 text-sm leading-6 text-muted-foreground">
                                <p>
                                    This demo uses the PayPal Sandbox. The buyer
                                    pays the agreed amount to the seller through
                                    PayPal.
                                </p>
                                <p>
                                    No escrow, split payment, or seller payout
                                    is claimed. Those capabilities require
                                    separate provider approval and are not
                                    enabled in this build.
                                </p>
                            </div>
                        )}

                        {currentStep === 'paypal' && (
                            <div className="space-y-4">
                                <div className="flex items-center justify-between gap-4 text-sm">
                                    <span className="text-muted-foreground">
                                        Amount
                                    </span>
                                    <span className="font-semibold">
                                        {formatMoney(amount, currency)}
                                    </span>
                                </div>
                                {canPay ? (
                                    <form
                                        method="POST"
                                        action={`/transactions/${transaction.id}/checkout`}
                                    >
                                        <input
                                            type="hidden"
                                            name="_token"
                                            value={csrfToken}
                                        />
                                        <Button type="submit">
                                            <CreditCard
                                                className="size-4"
                                                aria-hidden
                                            />
                                            Pay with PayPal
                                        </Button>
                                    </form>
                                ) : (
                                    <p className="text-sm text-muted-foreground">
                                        Only the buyer can start checkout, and
                                        only after both parties approve the
                                        agreement.
                                    </p>
                                )}
                                <p className="flex items-start gap-2 text-xs leading-5 text-muted-foreground">
                                    <ShieldCheck
                                        className="mt-0.5 size-3.5 shrink-0 text-emerald-700 dark:text-emerald-400"
                                        aria-hidden
                                    />
                                    The browser redirect is not treated as proof
                                    of payment. The server verifies the capture
                                    with PayPal.
                                </p>
                            </div>
                        )}

                        {currentStep === 'verify' && (
                            <div className="space-y-4 text-sm">
                                <p className="text-muted-foreground">
                                    PayPal has returned the buyer. The server is
                                    verifying the capture before the transaction
                                    is marked paid.
                                </p>
                                {payment?.paypal_approval_url && (
                                    <Button asChild>
                                        <a href={payment.paypal_approval_url}>
                                            Resume PayPal checkout
                                            <ExternalLink
                                                className="size-4"
                                                aria-hidden
                                            />
                                        </a>
                                    </Button>
                                )}
                                <p className="text-xs text-muted-foreground">
                                    Payment order:{' '}
                                    {payment?.paypal_order_id ?? 'pending'}
                                </p>
                            </div>
                        )}

                        {currentStep === 'result' && (
                            <div className="space-y-3 text-sm">
                                <p className="flex items-center gap-2 font-medium text-emerald-700 dark:text-emerald-400">
                                    <CheckCircle2
                                        className="size-4"
                                        aria-hidden
                                    />
                                    Payment verified by PayPal.
                                </p>
                                <p className="text-muted-foreground">
                                    Capture ID: {payment?.capture_id ?? '—'}
                                </p>
                            </div>
                        )}

                        {currentStep === 'order' && (
                            <div className="space-y-3 text-sm">
                                <p className="flex items-center gap-2 font-medium text-emerald-700 dark:text-emerald-400">
                                    <CheckCircle2
                                        className="size-4"
                                        aria-hidden
                                    />
                                    Order created.
                                </p>
                                <div className="flex items-center justify-between gap-4">
                                    <span className="text-muted-foreground">
                                        Order status
                                    </span>
                                    <Badge variant="outline">
                                        {order?.status ?? 'paid'}
                                    </Badge>
                                </div>
                                <div className="flex items-center justify-between gap-4">
                                    <span className="text-muted-foreground">
                                        Amount
                                    </span>
                                    <span className="font-medium">
                                        {formatMoney(
                                            order?.amount_minor ?? amount,
                                            order?.currency ?? currency,
                                        )}
                                    </span>
                                </div>
                                {order?.paid_at && (
                                    <p className="text-xs text-muted-foreground">
                                        Paid{' '}
                                        {new Date(
                                            order.paid_at,
                                        ).toLocaleString()}
                                    </p>
                                )}
                            </div>
                        )}
                    </div>

                    <div className="mt-8 flex flex-wrap gap-2 border-t border-border pt-5">
                        <Button asChild variant="outline">
                            <Link href={`/transactions/${transaction.id}`}>
                                Back to transaction
                            </Link>
                        </Button>
                    </div>
                </div>
            </div>
        </>
    );
}

CheckoutShow.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: '/dashboard',
        },
        {
            title: 'Transactions',
            href: '/transactions',
        },
        {
            title: 'Checkout',
            href: '/transactions',
        },
    ],
};
