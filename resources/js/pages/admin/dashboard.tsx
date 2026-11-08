import { Head, Link } from '@inertiajs/react';
import { ShieldAlert, Users, Wallet, Wrench } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { DealDispute } from '@/types';

type Stats = {
    users: number;
    services: number;
    transactions: number;
    open_disputes: number;
};

const cards = [
    { key: 'users', label: 'Users', icon: Users },
    { key: 'services', label: 'Services', icon: Wrench },
    { key: 'transactions', label: 'Transactions', icon: Wallet },
    { key: 'open_disputes', label: 'Open disputes', icon: ShieldAlert },
] as const;

export default function AdminDashboard({
    stats,
    recentDisputes,
}: {
    stats: Stats;
    recentDisputes: DealDispute[];
}) {
    return (
        <>
            <Head title="Admin" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div>
                    <p className="text-xs font-medium tracking-[0.22em] text-muted-foreground uppercase">
                        Administration
                    </p>
                    <h1 className="mt-2 text-2xl font-semibold tracking-tight">
                        Platform overview
                    </h1>
                    <p className="mt-1 max-w-2xl text-sm text-muted-foreground">
                        Users, listings, transactions, and dispute resolution.
                    </p>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {cards.map(({ key, label, icon: Icon }) => (
                        <Card key={key}>
                            <CardHeader className="flex-row items-start justify-between space-y-0">
                                <div>
                                    <CardDescription>{label}</CardDescription>
                                    <CardTitle className="mt-2 text-3xl">
                                        {stats[key]}
                                    </CardTitle>
                                </div>
                                <span className="rounded-lg bg-muted p-2 text-muted-foreground">
                                    <Icon className="size-5" aria-hidden />
                                </span>
                            </CardHeader>
                        </Card>
                    ))}
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <Link
                        href="/admin/users"
                        className="rounded-xl border border-border bg-card p-5 transition-colors hover:bg-muted/40"
                    >
                        <Users
                            className="size-4 text-muted-foreground"
                            aria-hidden
                        />
                        <p className="mt-3 text-sm font-medium">Manage users</p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Roles and account visibility.
                        </p>
                    </Link>
                    <Link
                        href="/admin/services"
                        className="rounded-xl border border-border bg-card p-5 transition-colors hover:bg-muted/40"
                    >
                        <Wrench
                            className="size-4 text-muted-foreground"
                            aria-hidden
                        />
                        <p className="mt-3 text-sm font-medium">
                            Moderate listings
                        </p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Publish, unpublish, or archive services.
                        </p>
                    </Link>
                    <Link
                        href="/admin/disputes"
                        className="rounded-xl border border-border bg-card p-5 transition-colors hover:bg-muted/40"
                    >
                        <ShieldAlert
                            className="size-4 text-muted-foreground"
                            aria-hidden
                        />
                        <p className="mt-3 text-sm font-medium">
                            Resolve disputes
                        </p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Review evidence and record resolutions.
                        </p>
                    </Link>
                </div>

                <Card>
                    <CardHeader className="flex-row items-center justify-between space-y-0">
                        <div>
                            <CardTitle>Recent disputes</CardTitle>
                            <CardDescription>
                                The latest order disputes across the platform.
                            </CardDescription>
                        </div>
                        <Link
                            href="/admin/disputes"
                            className="text-sm font-medium underline underline-offset-4"
                        >
                            View all
                        </Link>
                    </CardHeader>
                    <CardContent>
                        {recentDisputes.length === 0 ? (
                            <p className="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground">
                                No disputes yet.
                            </p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b text-left text-xs tracking-wide text-muted-foreground uppercase">
                                            <th className="py-3 pr-4 font-medium">
                                                Order
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Reason
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Opened by
                                            </th>
                                            <th className="py-3 pl-4 text-right font-medium">
                                                Status
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {recentDisputes.map((dispute) => (
                                            <tr
                                                key={dispute.id}
                                                className="border-b last:border-0"
                                            >
                                                <td className="py-3 pr-4">
                                                    <Link
                                                        href={`/admin/disputes/${dispute.id}`}
                                                        className="font-medium underline underline-offset-4"
                                                    >
                                                        {dispute.order
                                                            ?.transaction
                                                            ?.title ??
                                                            `Order #${dispute.order_id}`}
                                                    </Link>
                                                </td>
                                                <td className="px-4 py-3 text-muted-foreground">
                                                    {dispute.reason}
                                                </td>
                                                <td className="px-4 py-3 text-muted-foreground">
                                                    {dispute.opener?.name ??
                                                        '—'}
                                                </td>
                                                <td className="py-3 pl-4 text-right">
                                                    <Badge variant="outline">
                                                        {dispute.status.replaceAll(
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
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

AdminDashboard.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Admin', href: '/admin' },
    ],
};
