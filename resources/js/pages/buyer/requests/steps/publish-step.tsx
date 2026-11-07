import { Bell, Bot, ShieldCheck } from 'lucide-react';
import { formatMoney } from '@/lib/format';
import { asString, type BuyerStepProps } from './types';

export function PublishStep({ data, services }: BuyerStepProps) {
    const service = services.find(
        (item) => item.id === Number(data.service_id),
    );

    return (
        <div className="space-y-6">
            <div className="rounded-xl border border-border p-5">
                <p className="text-xs text-muted-foreground">
                    {service?.title ?? 'Service request'}
                </p>
                <h3 className="mt-2 text-xl font-semibold tracking-tight">
                    {asString(data.title, 'Untitled request')}
                </h3>
                <div className="mt-3 flex flex-wrap gap-4 text-sm">
                    <span className="font-semibold">
                        {formatMoney(
                            Math.round(Number(data.budget ?? 0) * 100),
                            asString(data.currency, 'USD'),
                        )}{' '}
                        max
                    </span>
                    <span className="text-muted-foreground">
                        {asString(data.delivery_days, '—')} day delivery
                    </span>
                    <span className="text-muted-foreground">
                        {data.negotiation_mode === 'ai'
                            ? 'AI-assisted'
                            : 'Manual'}
                    </span>
                </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-3">
                <div className="rounded-xl border border-border p-4">
                    <Bell
                        className="size-4 text-muted-foreground"
                        aria-hidden
                    />
                    <p className="mt-3 text-sm font-medium">Seller notified</p>
                    <p className="mt-1 text-sm leading-6 text-muted-foreground">
                        The seller receives a request notification with the
                        scope, budget, and delivery window.
                    </p>
                </div>
                <div className="rounded-xl border border-border p-4">
                    <Bot className="size-4 text-muted-foreground" aria-hidden />
                    <p className="mt-3 text-sm font-medium">
                        Agent setup comes next
                    </p>
                    <p className="mt-1 text-sm leading-6 text-muted-foreground">
                        For AI-assisted requests, both sides activate their own
                        agent before negotiation starts.
                    </p>
                </div>
                <div className="rounded-xl border border-border p-4">
                    <ShieldCheck
                        className="size-4 text-emerald-700 dark:text-emerald-400"
                        aria-hidden
                    />
                    <p className="mt-3 text-sm font-medium">No payment yet</p>
                    <p className="mt-1 text-sm leading-6 text-muted-foreground">
                        This creates a request, not a charge. Payment only
                        starts after both humans approve the final agreement.
                    </p>
                </div>
            </div>
        </div>
    );
}
