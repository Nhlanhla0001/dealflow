import { Head, Link } from '@inertiajs/react';
import { ArrowUpRight, Package } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatMoney } from '@/lib/format';
import type { DealOrder, OrderStatus } from '@/types';

const statusMeta: Record<
    OrderStatus,
    {
        label: string;
        variant: 'default' | 'secondary' | 'outline' | 'destructive';
    }
> = {
    paid: { label: 'Paid', variant: 'default' },
    in_progress: { label: 'In progress', variant: 'secondary' },
    revision_requested: { label: 'Revision requested', variant: 'outline' },
    delivered: { label: 'Delivered', variant: 'secondary' },
    completed: { label: 'Completed', variant: 'default' },
    disputed: { label: 'Disputed', variant: 'destructive' },
    cancelled: { label: 'Cancelled', variant: 'outline' },
};

export default function OrdersIndex({ orders }: { orders: DealOrder[] }) {
    return (
        <>
            <Head title="Orders" />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <div>
                    <p className="text-xs font-medium tracking-[0.22em] text-muted-foreground uppercase">
                        Fulfilment
                    </p>
                    <h1 className="mt-2 text-2xl font-semibold tracking-tight">
                        Orders
                    </h1>
                    <p className="mt-1 max-w-2xl text-sm text-muted-foreground">
                        Paid agreements become orders. Sellers deliver, buyers
                        review, and every consequential action is timestamped.
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Package className="size-4" aria-hidden />
                            Your orders
                        </CardTitle>
                        <CardDescription>
                            Orders where you are the buyer or the seller.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {orders.length === 0 ? (
                            <div className="rounded-lg border border-dashed p-8 text-center">
                                <p className="font-medium">No orders yet</p>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Orders appear after a PayPal payment is
                                    verified.
                                </p>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b text-left text-xs tracking-wide text-muted-foreground uppercase">
                                            <th className="py-3 pr-4 font-medium">
                                                Order
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Amount
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Status
                                            </th>
                                            <th className="py-3 pl-4 text-right font-medium">
                                                Action
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {orders.map((order) => {
                                            const meta =
                                                statusMeta[order.status];

                                            return (
                                                <tr
                                                    key={order.id}
                                                    className="border-b last:border-0"
                                                >
                                                    <td className="py-4 pr-4">
                                                        <p className="font-medium">
                                                            {order.transaction
                                                                ?.title ??
                                                                `Order #${order.id}`}
                                                        </p>
                                                        <p className="text-xs text-muted-foreground">
                                                            Order #{order.id}
                                                        </p>
                                                    </td>
                                                    <td className="px-4 py-4 font-medium">
                                                        {formatMoney(
                                                            order.amount_minor,
                                                            order.currency,
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-4">
                                                        <Badge
                                                            variant={
                                                                meta.variant
                                                            }
                                                        >
                                                            {meta.label}
                                                        </Badge>
                                                    </td>
                                                    <td className="py-4 pl-4 text-right">
                                                        <Link
                                                            href={`/orders/${order.id}`}
                                                            className="inline-flex items-center gap-1 text-sm font-medium underline underline-offset-4"
                                                        >
                                                            Open
                                                            <ArrowUpRight
                                                                className="size-3.5"
                                                                aria-hidden
                                                            />
                                                        </Link>
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

OrdersIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Orders', href: '/orders' },
    ],
};
