import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowUpRight, Plus } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatDate, formatMoney, stateMeta } from '@/lib/dealflow';
import { dashboard } from '@/routes';
import {
    create as transactionsCreate,
    index as transactionsIndex,
    show as transactionsShow,
} from '@/routes/transactions';
import type { Auth, DealTransaction } from '@/types';

type PageProps = {
    auth: Auth;
};

export default function TransactionsIndex({
    transactions,
}: {
    transactions: DealTransaction[];
}) {
    const { auth } = usePage<PageProps>().props;
    const isBuyer =
        auth.user.role === 'buyer' ||
        auth.user.role === 'both' ||
        auth.user.role === 'admin';

    return (
        <>
            <Head title="Transactions" />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p className="text-xs font-medium tracking-[0.22em] text-muted-foreground uppercase">
                            DealFlow
                        </p>
                        <h1 className="mt-2 text-2xl font-semibold tracking-tight">
                            Transactions
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Every negotiation, agreement, and payment in one
                            place.
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

                <Card>
                    <CardHeader>
                        <CardTitle>All deals</CardTitle>
                        <CardDescription>
                            {transactions.length} transaction
                            {transactions.length === 1 ? '' : 's'}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {transactions.length === 0 ? (
                            <div className="rounded-lg border border-dashed p-8 text-center">
                                <p className="font-medium">
                                    No transactions yet
                                </p>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    {isBuyer
                                        ? 'Create a request to start a negotiation.'
                                        : 'Transactions you are invited to will appear here.'}
                                </p>
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
                                                Buyer
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Seller
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Amount
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Status
                                            </th>
                                            <th className="py-3 pl-4 text-right font-medium">
                                                Created
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {transactions.map((transaction) => {
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
                                                        {transaction.buyer
                                                            ?.name ?? '—'}
                                                    </td>
                                                    <td className="px-4 py-3 text-muted-foreground">
                                                        {transaction.seller
                                                            ?.name ?? '—'}
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
                                                        {formatDate(
                                                            transaction.created_at,
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

                <Button asChild variant="outline" className="w-fit">
                    <Link href={dashboard()}>
                        <ArrowUpRight className="size-4" />
                        Back to dashboard
                    </Link>
                </Button>
            </div>
        </>
    );
}

TransactionsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
        {
            title: 'Transactions',
            href: transactionsIndex(),
        },
    ],
};
