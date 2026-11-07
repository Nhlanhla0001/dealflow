import { Head, Link, router } from '@inertiajs/react';
import {
    Archive,
    ArrowUpRight,
    EyeOff,
    Pencil,
    Plus,
    Send,
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
import { formatMoney } from '@/lib/format';
import type { MarketplaceService, ServiceStatus } from '@/types';

type Props = {
    services: MarketplaceService[];
    canCreate: boolean;
};

const statusMeta: Record<
    ServiceStatus,
    { label: string; variant: 'default' | 'secondary' | 'outline' }
> = {
    draft: { label: 'Draft', variant: 'outline' },
    published: { label: 'Published', variant: 'default' },
    unpublished: { label: 'Unpublished', variant: 'secondary' },
    archived: { label: 'Archived', variant: 'outline' },
};

export default function SellerServicesIndex({ services, canCreate }: Props) {
    const updateStatus = (
        service: MarketplaceService,
        status: ServiceStatus,
    ) => {
        router.patch(
            `/seller/services/${service.id}/status`,
            { status },
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title="My services" />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p className="text-xs font-medium tracking-[0.22em] text-muted-foreground uppercase">
                            Seller workspace
                        </p>
                        <h1 className="mt-2 text-2xl font-semibold tracking-tight">
                            My services
                        </h1>
                        <p className="mt-1 max-w-2xl text-sm text-muted-foreground">
                            Publish what you do, keep the terms clear, and let
                            buyers start a structured request.
                        </p>
                    </div>

                    {canCreate && (
                        <Button asChild>
                            <Link href="/seller/services/new">
                                <Plus className="size-4" aria-hidden />
                                New service
                            </Link>
                        </Button>
                    )}
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Listings</CardTitle>
                        <CardDescription>
                            Published listings appear in the public marketplace.
                            Drafts stay private.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {services.length === 0 ? (
                            <div className="rounded-lg border border-dashed p-8 text-center">
                                <p className="font-medium">No services yet</p>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Publish your first service to appear in the
                                    marketplace.
                                </p>
                                {canCreate && (
                                    <Button asChild className="mt-4">
                                        <Link href="/seller/services/new">
                                            <Plus
                                                className="size-4"
                                                aria-hidden
                                            />
                                            Publish a service
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
                                                Service
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Price
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Delivery
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Status
                                            </th>
                                            <th className="py-3 pl-4 text-right font-medium">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {services.map((service) => {
                                            const meta =
                                                statusMeta[service.status];

                                            return (
                                                <tr
                                                    key={service.id}
                                                    className="border-b last:border-0"
                                                >
                                                    <td className="py-4 pr-4">
                                                        <p className="font-medium">
                                                            {service.title}
                                                        </p>
                                                        <p className="mt-0.5 text-xs text-muted-foreground">
                                                            {service.category
                                                                ?.name ??
                                                                'Uncategorised'}
                                                        </p>
                                                    </td>
                                                    <td className="px-4 py-4 font-medium">
                                                        {formatMoney(
                                                            service.price_minor,
                                                            service.currency,
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-4 text-muted-foreground">
                                                        {service.delivery_days}{' '}
                                                        days
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
                                                    <td className="py-4 pl-4">
                                                        <div className="flex flex-wrap justify-end gap-1">
                                                            {service.status ===
                                                                'published' && (
                                                                <Button
                                                                    asChild
                                                                    variant="ghost"
                                                                    size="sm"
                                                                >
                                                                    <a
                                                                        href={`/services/${service.slug}`}
                                                                        target="_blank"
                                                                        rel="noreferrer"
                                                                    >
                                                                        <ArrowUpRight
                                                                            className="size-3.5"
                                                                            aria-hidden
                                                                        />
                                                                        View
                                                                    </a>
                                                                </Button>
                                                            )}
                                                            <Button
                                                                asChild
                                                                variant="ghost"
                                                                size="sm"
                                                            >
                                                                <Link
                                                                    href={`/seller/services/${service.id}/edit`}
                                                                >
                                                                    <Pencil
                                                                        className="size-3.5"
                                                                        aria-hidden
                                                                    />
                                                                    Edit
                                                                </Link>
                                                            </Button>
                                                            {service.status ===
                                                                'published' && (
                                                                <Button
                                                                    type="button"
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() =>
                                                                        updateStatus(
                                                                            service,
                                                                            'unpublished',
                                                                        )
                                                                    }
                                                                >
                                                                    <EyeOff
                                                                        className="size-3.5"
                                                                        aria-hidden
                                                                    />
                                                                    Unpublish
                                                                </Button>
                                                            )}
                                                            {service.status ===
                                                                'unpublished' && (
                                                                <Button
                                                                    type="button"
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() =>
                                                                        updateStatus(
                                                                            service,
                                                                            'published',
                                                                        )
                                                                    }
                                                                >
                                                                    <Send
                                                                        className="size-3.5"
                                                                        aria-hidden
                                                                    />
                                                                    Publish
                                                                </Button>
                                                            )}
                                                            {service.status !==
                                                                'archived' && (
                                                                <Button
                                                                    type="button"
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() =>
                                                                        updateStatus(
                                                                            service,
                                                                            'archived',
                                                                        )
                                                                    }
                                                                >
                                                                    <Archive
                                                                        className="size-3.5"
                                                                        aria-hidden
                                                                    />
                                                                    Archive
                                                                </Button>
                                                            )}
                                                        </div>
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

SellerServicesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: '/dashboard',
        },
        {
            title: 'My services',
            href: '/seller/services',
        },
    ],
};
