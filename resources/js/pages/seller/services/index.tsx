import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import {
    Archive,
    ArrowUpRight,
    EyeOff,
    Pencil,
    Plus,
    Send,
} from 'lucide-react';
import { ConfirmDialog } from '@/components/confirm-dialog';
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

function ServiceActions({
    service,
    onStatus,
    onArchive,
}: {
    service: MarketplaceService;
    onStatus: (service: MarketplaceService, status: ServiceStatus) => void;
    onArchive: (service: MarketplaceService) => void;
}) {
    return (
        <div className="flex flex-wrap items-center justify-start gap-1 md:justify-end">
            {service.status === 'published' && (
                <Button asChild variant="ghost" size="sm">
                    <a
                        href={`/services/${service.slug}`}
                        target="_blank"
                        rel="noreferrer"
                    >
                        <ArrowUpRight className="size-3.5" aria-hidden />
                        View
                    </a>
                </Button>
            )}
            <Button asChild variant="ghost" size="sm">
                <Link href={`/seller/services/${service.id}/edit`}>
                    <Pencil className="size-3.5" aria-hidden />
                    Edit
                </Link>
            </Button>
            {service.status === 'published' && (
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    onClick={() => onStatus(service, 'unpublished')}
                >
                    <EyeOff className="size-3.5" aria-hidden />
                    Unpublish
                </Button>
            )}
            {service.status === 'unpublished' && (
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    onClick={() => onStatus(service, 'published')}
                >
                    <Send className="size-3.5" aria-hidden />
                    Publish
                </Button>
            )}
            {service.status !== 'archived' && (
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    onClick={() => onArchive(service)}
                >
                    <Archive className="size-3.5" aria-hidden />
                    Archive
                </Button>
            )}
        </div>
    );
}

export default function SellerServicesIndex({ services, canCreate }: Props) {
    const [archiveTarget, setArchiveTarget] =
        useState<MarketplaceService | null>(null);

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
                                            <th className="hidden px-4 py-3 font-medium md:table-cell">
                                                Price
                                            </th>
                                            <th className="hidden px-4 py-3 font-medium md:table-cell">
                                                Delivery
                                            </th>
                                            <th className="hidden px-4 py-3 font-medium md:table-cell">
                                                Status
                                            </th>
                                            <th className="hidden py-3 pl-4 text-right font-medium md:table-cell">
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
                                                        <p className="mt-1 text-xs text-muted-foreground md:hidden">
                                                            {formatMoney(
                                                                service.price_minor,
                                                                service.currency,
                                                            )}{' '}
                                                            ·{' '}
                                                            {
                                                                service.delivery_days
                                                            }{' '}
                                                            days · {meta.label}
                                                        </p>
                                                        <div className="mt-2 md:hidden">
                                                            <ServiceActions
                                                                service={
                                                                    service
                                                                }
                                                                onStatus={
                                                                    updateStatus
                                                                }
                                                                onArchive={
                                                                    setArchiveTarget
                                                                }
                                                            />
                                                        </div>
                                                    </td>
                                                    <td className="hidden px-4 py-4 font-medium md:table-cell">
                                                        {formatMoney(
                                                            service.price_minor,
                                                            service.currency,
                                                        )}
                                                    </td>
                                                    <td className="hidden px-4 py-4 text-muted-foreground md:table-cell">
                                                        {service.delivery_days}{' '}
                                                        days
                                                    </td>
                                                    <td className="hidden px-4 py-4 md:table-cell">
                                                        <Badge
                                                            variant={
                                                                meta.variant
                                                            }
                                                        >
                                                            {meta.label}
                                                        </Badge>
                                                    </td>
                                                    <td className="hidden py-4 pl-4 md:table-cell">
                                                        <ServiceActions
                                                            service={service}
                                                            onStatus={
                                                                updateStatus
                                                            }
                                                            onArchive={
                                                                setArchiveTarget
                                                            }
                                                        />
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

            <ConfirmDialog
                open={archiveTarget !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setArchiveTarget(null);
                    }
                }}
                onConfirm={() => {
                    if (archiveTarget !== null) {
                        updateStatus(archiveTarget, 'archived');
                    }

                    setArchiveTarget(null);
                }}
                title="Archive this service?"
                description={`${
                    archiveTarget?.title ?? 'This service'
                } will leave the marketplace. Existing deals and orders are not affected. Archived services cannot be republished from this screen.`}
                confirmLabel="Archive service"
                cancelLabel="Keep service"
                destructive
            />
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
