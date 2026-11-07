import { formatMoney } from '@/lib/format';
import { WizardField } from '@/components/wizard/wizard-field';
import { asString, firstError, type BuyerStepProps } from './types';

export function ServiceStep({
    data,
    errors,
    services,
    updateField,
}: BuyerStepProps) {
    const selected = services.find(
        (service) => service.id === Number(data.service_id),
    );

    return (
        <div className="space-y-6">
            <WizardField
                label="Published service"
                htmlFor="service_id"
                required
                error={firstError(errors, 'service_id')}
                hint="Only published services can be requested."
            >
                <select
                    id="service_id"
                    value={asString(data.service_id)}
                    onChange={(event) =>
                        updateField(
                            'service_id',
                            event.target.value
                                ? Number(event.target.value)
                                : '',
                        )
                    }
                    className="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                >
                    <option value="">Choose a service</option>
                    {services.map((service) => (
                        <option key={service.id} value={service.id}>
                            {service.title} — {service.seller?.name ?? 'Seller'}
                        </option>
                    ))}
                </select>
            </WizardField>

            {selected && (
                <div className="rounded-xl border border-border p-5">
                    <div className="flex flex-wrap items-center justify-between gap-2 text-xs text-muted-foreground">
                        <span>{selected.category?.name ?? 'Service'}</span>
                        <span>
                            {selected.negotiation_enabled
                                ? 'Negotiable'
                                : 'Fixed terms'}
                        </span>
                    </div>
                    <h3 className="mt-2 text-lg font-semibold">
                        {selected.title}
                    </h3>
                    <p className="mt-2 text-sm leading-6 text-muted-foreground">
                        {selected.description}
                    </p>
                    <div className="mt-3 flex flex-wrap gap-4 text-sm">
                        <span className="font-medium">
                            {formatMoney(
                                selected.price_minor,
                                selected.currency,
                            )}
                        </span>
                        <span className="text-muted-foreground">
                            {selected.delivery_days} day delivery
                        </span>
                        {selected.seller && (
                            <span className="text-muted-foreground">
                                by {selected.seller.name}
                            </span>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}
