import { Eye, ShieldCheck } from 'lucide-react';
import { formatMoney } from '@/lib/format';
import { asString, type StepProps } from './types';

export function PublishStep({ data, categories }: StepProps) {
    const category = categories.find(
        (item) => item.id === Number(data.category_id),
    );

    return (
        <div className="space-y-6">
            <div className="rounded-xl border border-border p-5">
                <p className="text-xs text-muted-foreground">
                    {category?.name ?? 'Service'}
                </p>
                <h3 className="mt-2 text-xl font-semibold tracking-tight">
                    {asString(data.title, 'Untitled listing')}
                </h3>
                <div className="mt-3 flex flex-wrap gap-4 text-sm">
                    <span className="font-semibold">
                        {formatMoney(
                            Math.round(Number(data.price ?? 0) * 100),
                            asString(data.currency, 'USD'),
                        )}
                    </span>
                    <span className="text-muted-foreground">
                        {asString(data.delivery_days, '—')} day delivery
                    </span>
                    <span className="text-muted-foreground">
                        {data.negotiation_enabled
                            ? 'Negotiable'
                            : 'Fixed terms'}
                    </span>
                </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="rounded-xl border border-border p-4">
                    <Eye className="size-4 text-muted-foreground" aria-hidden />
                    <p className="mt-3 text-sm font-medium">
                        Publicly discoverable
                    </p>
                    <p className="mt-1 text-sm leading-6 text-muted-foreground">
                        The listing appears in the marketplace, category pages,
                        search, and the sitemap.
                    </p>
                </div>
                <div className="rounded-xl border border-border p-4">
                    <ShieldCheck
                        className="size-4 text-emerald-700 dark:text-emerald-400"
                        aria-hidden
                    />
                    <p className="mt-3 text-sm font-medium">
                        You stay in control
                    </p>
                    <p className="mt-1 text-sm leading-6 text-muted-foreground">
                        You can unpublish or archive the listing later. Buyers
                        still need your approval before any agreement becomes
                        binding.
                    </p>
                </div>
            </div>

            <p className="text-sm leading-6 text-muted-foreground">
                Publishing does not charge the buyer or start an order. It only
                makes the service available for discovery and requests.
            </p>
        </div>
    );
}
