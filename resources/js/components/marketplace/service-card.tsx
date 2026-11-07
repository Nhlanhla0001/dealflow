import { Link } from '@inertiajs/react';
import { ArrowUpRight, Clock3, Handshake } from 'lucide-react';
import { formatMoney } from '@/lib/format';
import type { MarketplaceService } from '@/types';

export function ServiceCard({ service }: { service: MarketplaceService }) {
    return (
        <article className="group flex h-full flex-col border-t border-border pt-5">
            <div className="flex items-center justify-between gap-3 text-xs">
                <span className="text-muted-foreground">
                    {service.category?.name ?? 'Service'}
                </span>
                {service.negotiation_enabled && (
                    <span className="inline-flex items-center gap-1 rounded-full border border-border px-2 py-0.5 text-[10px] text-muted-foreground">
                        <Handshake className="size-3" aria-hidden />
                        Negotiable
                    </span>
                )}
            </div>

            <h3 className="mt-3 text-base leading-6 font-semibold">
                <Link
                    href={`/services/${service.slug}`}
                    className="transition-colors hover:text-emerald-700 dark:hover:text-emerald-400"
                >
                    {service.title}
                </Link>
            </h3>

            <p className="mt-2 line-clamp-2 text-sm leading-6 text-muted-foreground">
                {service.description}
            </p>

            <div className="mt-4 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted-foreground">
                <span className="font-medium text-foreground">
                    {formatMoney(service.price_minor, service.currency)}
                </span>
                <span className="inline-flex items-center gap-1">
                    <Clock3 className="size-3" aria-hidden />
                    {service.delivery_days} days
                </span>
                {service.seller && <span>by {service.seller.name}</span>}
            </div>

            <div className="mt-auto pt-4">
                <Link
                    href={`/services/${service.slug}`}
                    className="inline-flex items-center gap-1 text-sm font-medium underline underline-offset-4 transition-colors hover:text-emerald-700 dark:hover:text-emerald-400"
                >
                    View service
                    <ArrowUpRight className="size-3.5" aria-hidden />
                </Link>
            </div>
        </article>
    );
}
