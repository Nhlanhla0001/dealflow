import { Head, Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { DealDispute, Paginated } from '@/types';

export default function AdminDisputes({
    disputes,
}: {
    disputes: Paginated<DealDispute>;
}) {
    return (
        <>
            <Head title="Admin — Disputes" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div>
                    <p className="text-xs font-medium tracking-[0.22em] text-muted-foreground uppercase">
                        Administration
                    </p>
                    <h1 className="mt-2 text-2xl font-semibold tracking-tight">
                        Disputes
                    </h1>
                    <p className="mt-1 max-w-2xl text-sm text-muted-foreground">
                        Review order disputes and record resolutions.
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Dispute queue</CardTitle>
                        <CardDescription>
                            {disputes.total} disputes.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
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
                                        <th className="px-4 py-3 font-medium">
                                            Status
                                        </th>
                                        <th className="py-3 pl-4 font-medium">
                                            Action
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {disputes.data.map((dispute) => (
                                        <tr
                                            key={dispute.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="py-3 pr-4">
                                                <Link
                                                    href={`/admin/disputes/${dispute.id}`}
                                                    className="font-medium underline underline-offset-4"
                                                >
                                                    {dispute.order?.transaction
                                                        ?.title ??
                                                        `Order #${dispute.order_id}`}
                                                </Link>
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {dispute.reason}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {dispute.opener?.name}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge variant="outline">
                                                    {dispute.status.replaceAll(
                                                        '_',
                                                        ' ',
                                                    )}
                                                </Badge>
                                            </td>
                                            <td className="py-3 pl-4">
                                                <Link
                                                    href={`/admin/disputes/${dispute.id}`}
                                                    className="text-sm font-medium underline underline-offset-4"
                                                >
                                                    Review
                                                </Link>
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

AdminDisputes.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Admin', href: '/admin' },
        { title: 'Disputes', href: '/admin/disputes' },
    ],
};
