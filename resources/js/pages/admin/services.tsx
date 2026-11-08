import { Head, router } from '@inertiajs/react';
import { Pagination } from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatMoney } from '@/lib/format';
import type { MarketplaceService, Paginated } from '@/types';

export default function AdminServices({
    services,
}: {
    services: Paginated<MarketplaceService>;
}) {
    const changeStatus = (service: MarketplaceService, status: string) => {
        router.patch(
            `/admin/services/${service.id}/status`,
            { status },
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title="Admin — Services" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div>
                    <p className="text-xs font-medium tracking-[0.22em] text-muted-foreground uppercase">
                        Administration
                    </p>
                    <h1 className="mt-2 text-2xl font-semibold tracking-tight">
                        Services
                    </h1>
                    <p className="mt-1 max-w-2xl text-sm text-muted-foreground">
                        Moderate published, unpublished, and archived listings.
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Listings</CardTitle>
                        <CardDescription>
                            {services.total} services.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left text-xs tracking-wide text-muted-foreground uppercase">
                                        <th className="py-3 pr-4 font-medium">
                                            Service
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Seller
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Price
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
                                    {services.data.map((service) => (
                                        <tr
                                            key={service.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="py-3 pr-4">
                                                <p className="font-medium">
                                                    {service.title}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {service.category?.name}
                                                </p>
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {service.seller?.name}
                                            </td>
                                            <td className="px-4 py-3">
                                                {formatMoney(
                                                    service.price_minor,
                                                    service.currency,
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge variant="outline">
                                                    {service.status}
                                                </Badge>
                                            </td>
                                            <td className="py-3 pl-4">
                                                <select
                                                    value={service.status}
                                                    onChange={(event) =>
                                                        changeStatus(
                                                            service,
                                                            event.target.value,
                                                        )
                                                    }
                                                    className="h-8 rounded-md border border-input bg-transparent px-2 text-xs"
                                                >
                                                    <option value="published">
                                                        published
                                                    </option>
                                                    <option value="unpublished">
                                                        unpublished
                                                    </option>
                                                    <option value="archived">
                                                        archived
                                                    </option>
                                                </select>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <Pagination paginator={services} itemLabel="services" />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

AdminServices.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Admin', href: '/admin' },
        { title: 'Services', href: '/admin/services' },
    ],
};
