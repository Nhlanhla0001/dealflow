import { Head, router } from '@inertiajs/react';
import { Coins, Plus, ReceiptText } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatMoney } from '@/lib/format';

type Entry = {
    id: number;
    type: string;
    amount_minor: number;
    balance_after_minor: number;
    description: string;
    created_at: string;
};

type Usage = {
    id: number;
    provider: string;
    model: string;
    prompt_tokens: number;
    completion_tokens: number;
    cost_minor: number;
    status: string;
    created_at: string;
    configuration?: {
        role: string;
        transaction_id: number;
    };
};

type Props = {
    balanceMinor: number;
    entries: Entry[];
    usage: Usage[];
};

export default function AiUsage({ balanceMinor, entries, usage }: Props) {
    const addCredit = () => {
        router.post('/ai/credits', {}, { preserveScroll: true });
    };

    return (
        <>
            <Head title="AI usage" />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p className="text-xs font-medium tracking-[0.22em] text-muted-foreground uppercase">
                            AI operations
                        </p>
                        <h1 className="mt-2 text-2xl font-semibold tracking-tight">
                            AI usage and credits
                        </h1>
                        <p className="mt-1 max-w-2xl text-sm text-muted-foreground">
                            Agent usage is metered separately from the deal
                            payment. Activation fails when the available budget
                            is too low.
                        </p>
                    </div>
                    <Button type="button" onClick={addCredit}>
                        <Plus className="size-4" aria-hidden />
                        Add $5 sandbox credit
                    </Button>
                </div>

                <div className="grid gap-4 sm:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardDescription>Available credit</CardDescription>
                            <CardTitle className="flex items-center gap-2 text-3xl">
                                <Coins
                                    className="size-5 text-emerald-700 dark:text-emerald-400"
                                    aria-hidden
                                />
                                {formatMoney(balanceMinor)}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardDescription>Ledger entries</CardDescription>
                            <CardTitle className="text-3xl">
                                {entries.length}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardDescription>Usage records</CardDescription>
                            <CardTitle className="text-3xl">
                                {usage.length}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                </div>

                <p className="rounded-lg border border-border bg-muted/40 p-3 text-xs leading-5 text-muted-foreground">
                    The “Add $5 sandbox credit” action is a demo ledger entry.
                    No real payment is charged. Production funding must use a
                    verified provider arrangement.
                </p>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <ReceiptText className="size-4" aria-hidden />
                            Credit ledger
                        </CardTitle>
                        <CardDescription>
                            Every credit and debit, with the resulting balance.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {entries.length === 0 ? (
                            <p className="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground">
                                No ledger entries yet.
                            </p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b text-left text-xs tracking-wide text-muted-foreground uppercase">
                                            <th className="py-3 pr-4 font-medium">
                                                Description
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Amount
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Balance
                                            </th>
                                            <th className="py-3 pl-4 text-right font-medium">
                                                Date
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {entries.map((entry) => (
                                            <tr
                                                key={entry.id}
                                                className="border-b last:border-0"
                                            >
                                                <td className="py-3 pr-4">
                                                    {entry.description}
                                                </td>
                                                <td
                                                    className={`px-4 py-3 font-medium ${
                                                        entry.amount_minor >= 0
                                                            ? 'text-emerald-700 dark:text-emerald-400'
                                                            : 'text-destructive'
                                                    }`}
                                                >
                                                    {entry.amount_minor >= 0
                                                        ? '+'
                                                        : ''}
                                                    {formatMoney(
                                                        entry.amount_minor,
                                                    )}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {formatMoney(
                                                        entry.balance_after_minor,
                                                    )}
                                                </td>
                                                <td className="py-3 pl-4 text-right text-muted-foreground">
                                                    {new Date(
                                                        entry.created_at,
                                                    ).toLocaleDateString()}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Recent usage</CardTitle>
                        <CardDescription>
                            Recorded agent calls and their cost.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {usage.length === 0 ? (
                            <p className="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground">
                                No agent usage has been recorded yet.
                            </p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b text-left text-xs tracking-wide text-muted-foreground uppercase">
                                            <th className="py-3 pr-4 font-medium">
                                                Agent
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Tokens
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Cost
                                            </th>
                                            <th className="py-3 pl-4 text-right font-medium">
                                                Status
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {usage.map((record) => (
                                            <tr
                                                key={record.id}
                                                className="border-b last:border-0"
                                            >
                                                <td className="py-3 pr-4">
                                                    <p className="font-medium">
                                                        {record.configuration
                                                            ?.role ?? 'Agent'}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {record.provider} ·{' '}
                                                        {record.model}
                                                    </p>
                                                </td>
                                                <td className="px-4 py-3 text-muted-foreground">
                                                    {record.prompt_tokens +
                                                        record.completion_tokens}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {formatMoney(
                                                        record.cost_minor,
                                                    )}
                                                </td>
                                                <td className="py-3 pl-4 text-right">
                                                    <Badge variant="outline">
                                                        {record.status}
                                                    </Badge>
                                                </td>
                                            </tr>
                                        ))}
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

AiUsage.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: '/dashboard',
        },
        {
            title: 'AI usage',
            href: '/ai/usage',
        },
    ],
};
