import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowUpRight,
    CheckCircle2,
    Clock3,
    Handshake,
    Plus,
    Wallet,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';
import {
    create as transactionsCreate,
    index as transactionsIndex,
    show as transactionsShow,
} from '@/routes/transactions';
import type {
    Auth,
    DealStats,
    DealTransaction,
    TransactionState,
} from '@/types';

type PageProps = {
    auth: Auth;
};

type BadgeVariant = 'default' | 'secondary' | 'outline' | 'destructive';

const stateMeta: Record<
    TransactionState,
    { label: string; variant: BadgeVariant }
> = {
    draft: { label: 'Draft', variant: 'outline' },
    negotiating: { label: 'Negotiating', variant: 'default' },
    agreement_proposed: { label: 'Agreement proposed', variant: 'secondary' },
    awaiting_approvals: { label: 'Awaiting approvals', variant: 'secondary' },
    approved: { label: 'Approved', variant: 'default' },
    payment_pending: { label: 'Payment pending', variant: 'secondary' },
    paid: { label: 'Paid', variant: 'default' },
    cancelled: { label: 'Cancelled', variant: 'outline' },
    failed: { label: 'Failed', variant: 'destructive' },
};

function formatMoney(minor: number, currency: string): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency,
        maximumFractionDigits: 0,
    }).format(minor / 100);
}

function StatCard({
    label,
    value,
    hint,
    icon: Icon,
}: {
    label: string;
    value: number;
    hint: string;
    icon: typeof Handshake;
}) {
    return (
        <Card className="gap-4 py-5">
            <CardHeader className="flex-row items-start justify-between space-y-0 px-5">
                <div>
                    <CardDescription>{label}</CardDescription>
                    <CardTitle className="mt-2 text-3xl">{value}</CardTitle>
                </div>
                <span className="rounded-lg bg-muted p-2 text-muted-foreground">
                    <Icon className="size-5" />
                </span>
            </CardHeader>
            <CardContent className="px-5">
                <p className="text-xs text-muted-foreground">{hint}</p>
            </CardContent>
        </Card>
    );
}

export default function Dashboard({
    transactions,
    stats,
}: {
    transactions: DealTransaction[];
    stats: DealStats;
}) {
    const { auth } = usePage<PageProps>().props;
    const isBuyer = auth.user.role === 'buyer';

    return (
        <>
            <Head title="Dashboard" />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p className="text-xs font-medium tracking-[0.22em] text-muted-foreground uppercase">
                            DealFlow AI
                        </p>
                        <h1 className="mt-2 text-2xl font-semibold tracking-tight">
                            Deal room
                        </h1>
                        <p className="mt-1 max-w-2xl text-sm text-muted-foreground">
                            AI agents negotiate inside your limits. You approve
                            the terms. PayPal settles the payment.
                        </p>
                    </div>

                    {isBuyer && (
                        <Button asChild>
                            <Link href={transactionsCreate()}>
                                <Plus className="size-4" />
                                New transaction
                            </Link>
                        </Button>
                    )}
                </div>

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        label="Open deals"
                        value={stats.open}
                        hint="Active and not yet settled"
                        icon={Handshake}
                    />
                    <StatCard
                        label="Negotiating"
                        value={stats.negotiating}
                        hint="Agents are working the terms"
                        icon={Clock3}
                    />
                    <StatCard
                        label="Awaiting approvals"
                        value={stats.awaiting_approvals}
                        hint="Both parties must approve"
                        icon={CheckCircle2}
                    />
                    <StatCard
                        label="Paid"
                        value={stats.paid}
                        hint="Verified PayPal payments"
                        icon={Wallet}
                    />
                </div>

                <Card>
                    <CardHeader className="flex-row flex-wrap items-center justify-between gap-3 space-y-0">
                        <div>
                            <CardTitle>Recent transactions</CardTitle>
                            <CardDescription>
                                The latest deals you are part of.
                            </CardDescription>
                        </div>
                        <Button asChild variant="outline" size="sm">
                            <Link href={transactionsIndex()}>
                                View all
                                <ArrowUpRight className="size-4" />
                            </Link>
                        </Button>
                    </CardHeader>

                    <CardContent>
                        {transactions.length === 0 ? (
                            <div className="rounded-lg border border-dashed p-8 text-center">
                                <p className="font-medium">
                                    No transactions yet
                                </p>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    {isBuyer
                                        ? 'Create your first request and invite a seller to negotiate.'
                                        : 'Transactions you are invited to will appear here.'}
                                </p>
                                {isBuyer && (
                                    <Button asChild className="mt-4" size="sm">
                                        <Link href={transactionsCreate()}>
                                            <Plus className="size-4" />
                                            New transaction
                                        </Link>
                                    </Button>
                                )}
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b text-left text-xs tracking-wide text-muted-foreground uppercase">
                                            <th className="py-3 pr-4 font-medium">
                                                Deal
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Counterparty
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Amount
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Status
                                            </th>
                                            <th className="py-3 pl-4 text-right font-medium">
                                                Updated
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {transactions.map((transaction) => {
                                            const counterparty =
                                                transaction.buyer_id ===
                                                auth.user.id
                                                    ? transaction.seller
                                                    : transaction.buyer;
                                            const meta =
                                                stateMeta[transaction.state];

                                            return (
                                                <tr
                                                    key={transaction.id}
                                                    className="border-b last:border-0"
                                                >
                                                    <td className="py-3 pr-4">
                                                        <Link
                                                            href={transactionsShow(
                                                                transaction.id,
                                                            )}
                                                            className="font-medium hover:underline"
                                                        >
                                                            {transaction.title}
                                                        </Link>
                                                        <p className="mt-0.5 text-xs text-muted-foreground">
                                                            #{transaction.id} ·{' '}
                                                            {
                                                                transaction.delivery_days
                                                            }{' '}
                                                            day delivery
                                                        </p>
                                                    </td>
                                                    <td className="px-4 py-3 text-muted-foreground">
                                                        {counterparty?.name ??
                                                            '—'}
                                                    </td>
                                                    <td className="px-4 py-3 font-medium">
                                                        {formatMoney(
                                                            transaction.offer
                                                                ?.price_minor ??
                                                                transaction.budget_minor,
                                                            transaction.currency,
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <Badge
                                                            variant={
                                                                meta.variant
                                                            }
                                                        >
                                                            {meta.label}
                                                        </Badge>
                                                    </td>
                                                    <td className="py-3 pl-4 text-right text-muted-foreground">
                                                        {new Date(
                                                            transaction.updated_at,
                                                        ).toLocaleDateString(
                                                            undefined,
                                                            {
                                                                month: 'short',
                                                                day: 'numeric',
                                                            },
                                                        )}
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
