import { Head, Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatMoney } from '@/lib/format';
import type { Paginated, DealTransaction } from '@/types';

export default function AdminTransactions({
    transactions,
}: {
    transactions: Paginated<DealTransaction>;
}) {
    return (
        <>
            <Head title="Admin — Transactions" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div>
                    <p className="text-xs font-medium tracking-[0.22em] text-muted-foreground uppercase">
                        Administration
                    </p>
                    <h1 className="mt-2 text-2xl font-semibold tracking-tight">
                        Transactions
                    </h1>
                    <p className="mt-1 max-w-2xl text-sm text-muted-foreground">
                        Read-only visibility into every deal on the platform.
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>All transactions</CardTitle>
                        <CardDescription>
                            {transactions.total} records.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
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
                                            Budget
                                        </th>
                                        <th className="py-3 pl-4 font-medium">
                                            State
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {transactions.data.map((transaction) => (
                                        <tr
                                            key={transaction.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="py-3 pr-4">
                                                <Link
                                                    href={`/transactions/${transaction.id}`}
                                                    className="font-medium underline underline-offset-4"
                                                >
                                                    {transaction.title}
                                                </Link>
                                                <p className="text-xs text-muted-foreground">
                                                    #{transaction.id}
                                                </p>
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {transaction.buyer?.name}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {transaction.seller?.name}
                                            </td>
                                            <td className="px-4 py-3">
                                                {formatMoney(
                                                    transaction.budget_minor,
                                                    transaction.currency,
                                                )}
                                            </td>
                                            <td className="py-3 pl-4">
                                                <Badge variant="outline">
                                                    {transaction.state.replaceAll(
                                                        '_',
                                                        ' ',
                                                    )}
                                                </Badge>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

AdminTransactions.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Admin', href: '/admin' },
        { title: 'Transactions', href: '/admin/transactions' },
    ],
};
