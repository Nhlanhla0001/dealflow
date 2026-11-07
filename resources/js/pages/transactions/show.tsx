import { Form, Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    ArrowRight,
    CheckCircle2,
    CircleDollarSign,
    FileText,
    MessageSquare,
    ShieldCheck,
} from 'lucide-react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { formatDate, formatMoney, stateMeta } from '@/lib/dealflow';
import { dashboard } from '@/routes';
import {
    approve as approveAgreement,
    reject as rejectAgreement,
} from '@/routes/agreements';
import {
    checkout as checkoutStore,
    index as transactionsIndex,
    negotiate as negotiateStore,
    show as transactionsShow,
} from '@/routes/transactions';
import { store as offerStore } from '@/routes/transactions/offer';
import type { Auth, DealTransaction } from '@/types';

type PageProps = {
    auth: Auth;
};

function EmptyState({ children }: { children: React.ReactNode }) {
    return (
        <p className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
            {children}
        </p>
    );
}

export default function TransactionsShow({
    transaction,
    can,
    hasApproved,
}: {
    transaction: DealTransaction;
    can: {
        update: boolean;
        delete: boolean;
        approve: boolean;
    };
    hasApproved: boolean;
}) {
    const { auth } = usePage<PageProps>().props;
    const meta = stateMeta[transaction.state];
    const isBuyer = transaction.buyer_id === auth.user.id;
    const isSeller = transaction.seller_id === auth.user.id;
    const counterparty = isBuyer ? transaction.seller : transaction.buyer;
    const canStartNegotiation =
        isBuyer &&
        transaction.state === 'draft' &&
        transaction.offer !== null &&
        !transaction.negotiation_session;
    const canSubmitOffer = isSeller && transaction.state === 'draft';
    const csrfToken =
        typeof document !== 'undefined'
            ? (document
                  .querySelector('meta[name="csrf-token"]')
                  ?.getAttribute('content') ?? '')
            : '';
    const price =
        transaction.latest_agreement?.price_minor ??
        transaction.offer?.price_minor ??
        transaction.budget_minor;
    const delivery =
        transaction.latest_agreement?.delivery_days ??
        transaction.delivery_days;
    const negotiation = transaction.negotiation_session;
    const agreement = transaction.latest_agreement;
    const payment = transaction.payment_orders?.[0];

    return (
        <>
            <Head title={transaction.title} />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div className="flex flex-wrap items-center gap-2">
                            <Badge variant={meta.variant}>{meta.label}</Badge>
                            <span className="text-xs text-muted-foreground">
                                Transaction #{transaction.id}
                            </span>
                        </div>
                        <h1 className="mt-3 text-2xl font-semibold tracking-tight">
                            {transaction.title}
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {transaction.buyer?.name} →{' '}
                            {transaction.seller?.name} ·{' '}
                            {formatMoney(price, transaction.currency)} ·{' '}
                            {delivery} day delivery
                        </p>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <Button asChild variant="outline">
                            <Link href={transactionsIndex()}>
                                <ArrowLeft className="size-4" />
                                All transactions
                            </Link>
                        </Button>
                    </div>
                </div>

                {transaction.state === 'awaiting_approvals' && agreement && (
                    <Card className="border-amber-500/40 bg-amber-50 dark:bg-amber-950/20">
                        <CardContent className="flex flex-wrap items-center justify-between gap-3 py-4">
                            <div>
                                <p className="font-medium">
                                    Terms ready for your approval
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    {can.approve
                                        ? 'Review the agreement and approve or reject it.'
                                        : hasApproved
                                          ? 'You approved. Waiting for the other party.'
                                          : 'Waiting for both parties to approve.'}
                                </p>
                            </div>
                            <Button asChild variant="outline">
                                <a href="#agreement">Review agreement</a>
                            </Button>
                        </CardContent>
                    </Card>
                )}

                <div className="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(280px,1fr)]">
                    <div className="space-y-6">
                        <Card>
                            <CardHeader className="flex-row items-start justify-between gap-4 space-y-0">
                                <div>
                                    <CardTitle className="flex items-center gap-2">
                                        <FileText className="size-4" />
                                        Request
                                    </CardTitle>
                                    <CardDescription>
                                        The buyer's scope, budget, and delivery
                                        expectation.
                                    </CardDescription>
                                </div>
                                <div className="text-right text-sm">
                                    <p className="font-medium">
                                        {formatMoney(
                                            transaction.budget_minor,
                                            transaction.currency,
                                        )}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        max budget
                                    </p>
                                </div>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <p className="text-sm leading-6 whitespace-pre-line text-muted-foreground">
                                    {transaction.description}
                                </p>
                                <Separator />
                                <div className="grid gap-3 text-sm sm:grid-cols-3">
                                    <div>
                                        <p className="text-xs text-muted-foreground">
                                            Preferred delivery
                                        </p>
                                        <p className="font-medium">
                                            {transaction.delivery_days} days
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-xs text-muted-foreground">
                                            Currency
                                        </p>
                                        <p className="font-medium">
                                            {transaction.currency}
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-xs text-muted-foreground">
                                            Created
                                        </p>
                                        <p className="font-medium">
                                            {formatDate(transaction.created_at)}
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Seller offer</CardTitle>
                                <CardDescription>
                                    {isSeller
                                        ? 'Set the opening price, delivery window, and a private floor the buyer never sees.'
                                        : "The seller's opening price, delivery window, and terms."}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {canSubmitOffer ? (
                                    <Form
                                        {...offerStore.form({
                                            transaction: transaction.id,
                                        })}
                                        className="space-y-4"
                                    >
                                        {({ processing, errors }) => (
                                            <>
                                                <div className="grid gap-4 sm:grid-cols-2">
                                                    <div className="grid gap-2">
                                                        <Label htmlFor="price">
                                                            Offer price (USD)
                                                        </Label>
                                                        <Input
                                                            id="price"
                                                            name="price"
                                                            type="number"
                                                            min="1"
                                                            step="0.01"
                                                            defaultValue={
                                                                transaction.offer
                                                                    ? transaction
                                                                          .offer
                                                                          .price_minor /
                                                                      100
                                                                    : ''
                                                            }
                                                            required
                                                        />
                                                        <InputError
                                                            message={
                                                                errors.price_minor
                                                            }
                                                        />
                                                    </div>
                                                    <div className="grid gap-2">
                                                        <Label htmlFor="floor">
                                                            Minimum acceptable
                                                            (private)
                                                        </Label>
                                                        <Input
                                                            id="floor"
                                                            name="floor"
                                                            type="number"
                                                            min="1"
                                                            step="0.01"
                                                            defaultValue={
                                                                transaction.seller_floor_minor !==
                                                                null
                                                                    ? transaction.seller_floor_minor /
                                                                      100
                                                                    : ''
                                                            }
                                                            required
                                                        />
                                                        <p className="text-xs text-muted-foreground">
                                                            Never shown to the
                                                            buyer.
                                                        </p>
                                                        <InputError
                                                            message={
                                                                errors.floor_minor
                                                            }
                                                        />
                                                    </div>
                                                </div>
                                                <div className="grid gap-4 sm:grid-cols-2">
                                                    <div className="grid gap-2">
                                                        <Label htmlFor="offer_delivery_days">
                                                            Delivery (days)
                                                        </Label>
                                                        <Input
                                                            id="offer_delivery_days"
                                                            name="delivery_days"
                                                            type="number"
                                                            min="1"
                                                            max="365"
                                                            defaultValue={
                                                                transaction.offer
                                                                    ?.delivery_days ??
                                                                transaction.delivery_days
                                                            }
                                                            required
                                                        />
                                                        <InputError
                                                            message={
                                                                errors.delivery_days
                                                            }
                                                        />
                                                    </div>
                                                    <div className="grid gap-2">
                                                        <Label htmlFor="terms">
                                                            Terms
                                                        </Label>
                                                        <Input
                                                            id="terms"
                                                            name="terms"
                                                            defaultValue={
                                                                transaction.offer
                                                                    ?.terms ?? ''
                                                            }
                                                            placeholder="Payment on delivery, two revision rounds"
                                                        />
                                                        <InputError
                                                            message={errors.terms}
                                                        />
                                                    </div>
                                                </div>
                                                <Button disabled={processing}>
                                                    {transaction.offer
                                                        ? 'Update offer'
                                                        : 'Submit offer'}
                                                </Button>
                                            </>
                                        )}
                                    </Form>
                                ) : transaction.offer ? (
                                    <div className="space-y-3">
                                        <div className="flex flex-wrap items-center justify-between gap-3">
                                            <div>
                                                <p className="text-sm font-medium">
                                                    {formatMoney(
                                                        transaction.offer
                                                            .price_minor,
                                                        transaction.offer
                                                            .currency,
                                                    )}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {
                                                        transaction.offer
                                                            .delivery_days
                                                    }{' '}
                                                    day delivery
                                                </p>
                                            </div>
                                            <Badge variant="outline">
                                                {transaction.offer.status}
                                            </Badge>
                                        </div>
                                        {transaction.offer.terms && (
                                            <p className="text-sm text-muted-foreground">
                                                {transaction.offer.terms}
                                            </p>
                                        )}
                                        {isSeller &&
                                            transaction.seller_floor_minor !==
                                                null && (
                                                <p className="text-xs text-muted-foreground">
                                                    Private floor:{' '}
                                                    {formatMoney(
                                                        transaction
                                                            .seller_floor_minor,
                                                        transaction.currency,
                                                    )}
                                                </p>
                                            )}
                                    </div>
                                ) : (
                                    <EmptyState>
                                        Waiting for the seller to submit an
                                        offer.
                                    </EmptyState>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <MessageSquare className="size-4" />
                                    Negotiation
                                </CardTitle>
                                <CardDescription>
                                    Buyer and seller agents negotiate only
                                    inside the limits each party approved.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {negotiation?.messages?.length ? (
                                    <div className="space-y-3">
                                        {negotiation.messages.map(
                                            (message) => (
                                                <div
                                                    key={message.id}
                                                    className="rounded-lg border p-3"
                                                >
                                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                                        <Badge
                                                            variant={
                                                                message.agent ===
                                                                'buyer'
                                                                    ? 'secondary'
                                                                    : 'outline'
                                                            }
                                                        >
                                                            {message.agent ===
                                                            'buyer'
                                                                ? 'Buyer agent'
                                                                : 'Seller agent'}
                                                        </Badge>
                                                        <span className="text-xs text-muted-foreground">
                                                            Round{' '}
                                                            {message.round}
                                                        </span>
                                                    </div>
                                                    <p className="mt-2 text-sm">
                                                        {message.action ===
                                                        'offer'
                                                            ? 'Proposed terms'
                                                            : message.action}
                                                        {message.price_minor !==
                                                        null
                                                            ? ` · ${formatMoney(
                                                                  message.price_minor,
                                                                  message.currency ??
                                                                      transaction.currency,
                                                              )}`
                                                            : ''}
                                                        {message.delivery_days !==
                                                        null
                                                            ? ` · ${message.delivery_days} days`
                                                            : ''}
                                                    </p>
                                                    {message.reason && (
                                                        <p className="mt-1 text-xs text-muted-foreground">
                                                            {message.reason}
                                                        </p>
                                                    )}
                                                </div>
                                            ),
                                        )}
                                    </div>
                                ) : canStartNegotiation ? (
                                    <div className="space-y-3">
                                        <p className="text-sm text-muted-foreground">
                                            The seller has submitted an offer.
                                            Start the AI negotiation to produce
                                            an agreement inside both parties'
                                            limits.
                                        </p>
                                        <Form
                                            {...negotiateStore.form({
                                                transaction: transaction.id,
                                            })}
                                        >
                                            {({ processing }) => (
                                                <Button disabled={processing}>
                                                    {processing
                                                        ? 'Negotiating…'
                                                        : 'Start AI negotiation'}
                                                </Button>
                                            )}
                                        </Form>
                                    </div>
                                ) : (
                                    <EmptyState>
                                        No negotiation messages yet. The
                                        seller must submit an offer before the
                                        buyer can start negotiation.
                                    </EmptyState>
                                )}
                            </CardContent>
                        </Card>

                        <Card id="agreement">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <ShieldCheck className="size-4" />
                                    Agreement
                                </CardTitle>
                                <CardDescription>
                                    Both parties approve the same immutable
                                    version. A material change resets approvals.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {agreement ? (
                                    <div className="space-y-4">
                                        <div className="flex flex-wrap items-center justify-between gap-2">
                                            <div>
                                                <p className="text-sm font-medium">
                                                    Version{' '}
                                                    {agreement.version}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {formatMoney(
                                                        agreement.price_minor,
                                                        agreement.currency,
                                                    )}{' '}
                                                    ·{' '}
                                                    {
                                                        agreement.delivery_days
                                                    }{' '}
                                                    days
                                                </p>
                                            </div>
                                            <Badge variant="outline">
                                                {agreement.status.replaceAll(
                                                    '_',
                                                    ' ',
                                                )}
                                            </Badge>
                                        </div>
                                        <p className="text-xs text-muted-foreground">
                                            {agreement.approvals?.length ?? 0} of
                                            2 approvals recorded.
                                        </p>
                                        {agreement.status === 'approved' ? (
                                            <p className="text-sm font-medium text-emerald-600">
                                                Approved by both parties.
                                            </p>
                                        ) : can.approve ? (
                                            <div className="flex flex-wrap gap-2">
                                                <Form
                                                    {...approveAgreement.form({
                                                        agreement: agreement.id,
                                                    })}
                                                >
                                                    {({ processing }) => (
                                                        <Button
                                                            disabled={processing}
                                                        >
                                                            {processing
                                                                ? 'Approving…'
                                                                : 'Approve this version'}
                                                        </Button>
                                                    )}
                                                </Form>
                                                <Form
                                                    {...rejectAgreement.form({
                                                        agreement: agreement.id,
                                                    })}
                                                >
                                                    {({ processing }) => (
                                                        <Button
                                                            variant="outline"
                                                            disabled={processing}
                                                        >
                                                            {processing
                                                                ? 'Rejecting…'
                                                                : 'Reject terms'}
                                                        </Button>
                                                    )}
                                                </Form>
                                            </div>
                                        ) : hasApproved ? (
                                            <p className="text-xs text-muted-foreground">
                                                You approved this version.
                                                Waiting for the other party.
                                            </p>
                                        ) : null}
                                    </div>
                                ) : (
                                    <EmptyState>
                                        No agreement yet. It will appear after
                                        the agents settle on compatible terms.
                                    </EmptyState>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <CircleDollarSign className="size-4" />
                                    Payment
                                </CardTitle>
                                <CardDescription>
                                    PayPal sandbox checkout. Payment is
                                    verified server-side before the transaction
                                    is marked paid.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {payment ? (
                                    <div className="space-y-3">
                                        <div className="flex flex-wrap items-center justify-between gap-3">
                                            <div>
                                                <p className="text-sm font-medium">
                                                    {formatMoney(
                                                        payment.amount_minor,
                                                        payment.currency,
                                                    )}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    PayPal order{' '}
                                                    {payment.paypal_order_id ??
                                                        'pending'}
                                                </p>
                                                {payment.capture_id && (
                                                    <p className="text-xs text-muted-foreground">
                                                        Capture{' '}
                                                        {payment.capture_id}
                                                    </p>
                                                )}
                                            </div>
                                            <Badge variant="outline">
                                                {payment.status}
                                            </Badge>
                                        </div>
                                        {payment.status === 'created' &&
                                            payment.paypal_approval_url &&
                                            isBuyer && (
                                                <Button asChild>
                                                    <a
                                                        href={
                                                            payment.paypal_approval_url
                                                        }
                                                    >
                                                        Resume PayPal checkout
                                                    </a>
                                                </Button>
                                            )}
                                        {payment.status === 'captured' && (
                                            <p className="text-sm font-medium text-emerald-600">
                                                Payment verified by PayPal.
                                            </p>
                                        )}
                                    </div>
                                ) : transaction.state === 'approved' &&
                                  isBuyer ? (
                                    <div className="space-y-3">
                                        <p className="text-sm text-muted-foreground">
                                            Both parties approved the agreement.
                                            Continue to PayPal to complete the
                                            sandbox payment.
                                        </p>
                                        <form
                                            method="POST"
                                            action={checkoutStore.url({
                                                transaction: transaction.id,
                                            })}
                                        >
                                            <input
                                                type="hidden"
                                                name="_token"
                                                value={csrfToken}
                                            />
                                            <Button type="submit">
                                                Pay with PayPal
                                            </Button>
                                        </form>
                                    </div>
                                ) : transaction.state === 'approved' ? (
                                    <EmptyState>
                                        Waiting for the buyer to complete the
                                        PayPal sandbox checkout.
                                    </EmptyState>
                                ) : (
                                    <EmptyState>
                                        No PayPal order yet. Checkout becomes
                                        available after both parties approve the
                                        agreement.
                                    </EmptyState>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Parties</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <p className="text-xs text-muted-foreground">
                                        Buyer
                                    </p>
                                    <p className="text-sm font-medium">
                                        {transaction.buyer?.name}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {transaction.buyer?.email}
                                    </p>
                                </div>
                                <Separator />
                                <div>
                                    <p className="text-xs text-muted-foreground">
                                        Seller
                                    </p>
                                    <p className="text-sm font-medium">
                                        {transaction.seller?.name}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {transaction.seller?.email}
                                    </p>
                                </div>
                                <Separator />
                                <div>
                                    <p className="text-xs text-muted-foreground">
                                        You are the{' '}
                                        {transaction.buyer_id === auth.user.id
                                            ? 'buyer'
                                            : 'seller'}
                                    </p>
                                    <p className="text-sm font-medium">
                                        {auth.user.name}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Next step</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm text-muted-foreground">
                                {transaction.state === 'draft' &&
                                !transaction.offer ? (
                                    <>
                                        <p>
                                            Waiting for{' '}
                                            {counterparty?.name ??
                                                'the seller'}{' '}
                                            to submit an offer.
                                        </p>
                                        <p className="flex items-center gap-2 text-xs">
                                            <ArrowRight className="size-3" />
                                            Seller offer → agent negotiation →
                                            dual approval → PayPal
                                        </p>
                                    </>
                                ) : transaction.state === 'draft' &&
                                  transaction.offer ? (
                                    <>
                                        <p>
                                            The seller's offer is in. Start the
                                            AI negotiation to produce an
                                            agreement inside both limits.
                                        </p>
                                        <p className="flex items-center gap-2 text-xs">
                                            <ArrowRight className="size-3" />
                                            Offer → negotiation → dual approval
                                            → PayPal
                                        </p>
                                    </>
                                ) : transaction.state ===
                                  'awaiting_approvals' ? (
                                    <p>
                                        Both parties must approve the same
                                        agreement version before payment can
                                        start.
                                    </p>
                                ) : transaction.state === 'approved' ? (
                                    <p>
                                        The agreement is approved. The buyer can
                                        complete PayPal sandbox checkout now.
                                    </p>
                                ) : transaction.state === 'paid' ? (
                                    <p>
                                        Payment is verified. This deal is
                                        complete.
                                    </p>
                                ) : transaction.state === 'failed' ||
                                  transaction.state === 'cancelled' ? (
                                    <p>
                                        This deal did not complete. The
                                        negotiation and audit history remain
                                        available.
                                    </p>
                                ) : (
                                    <p>Negotiation is in progress.</p>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Activity</CardTitle>
                                <CardDescription>
                                    Every state change is recorded.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {transaction.audit_logs?.length ? (
                                    <ol className="space-y-3">
                                        {transaction.audit_logs.map((log) => (
                                            <li
                                                key={log.id}
                                                className="flex gap-3 text-sm"
                                            >
                                                <CheckCircle2 className="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                                                <div>
                                                    <p className="font-medium capitalize">
                                                        {log.action
                                                            .replaceAll('.', ' ')
                                                            .replaceAll('_', ' ')}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {formatDate(
                                                            log.created_at,
                                                        )}
                                                    </p>
                                                </div>
                                            </li>
                                        ))}
                                    </ol>
                                ) : (
                                    <EmptyState>
                                        No activity recorded yet.
                                    </EmptyState>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <Button asChild variant="ghost" className="w-fit">
                    <Link href={transactionsShow(transaction.id)}>
                        Refresh
                    </Link>
                </Button>
            </div>
        </>
    );
}

TransactionsShow.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
        {
            title: 'Transactions',
            href: transactionsIndex(),
        },
        {
            title: 'Transaction',
            href: '/transactions',
        },
    ],
};
