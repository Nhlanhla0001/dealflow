import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { WizardField } from '@/components/wizard/wizard-field';
import { asString, firstError, type StepProps } from './types';

const pricingTypes = [
    { value: 'fixed', label: 'Fixed price' },
    { value: 'hourly', label: 'Hourly' },
    { value: 'starting_at', label: 'Starting at' },
];

export function PricingStep({ data, errors, updateField }: StepProps) {
    return (
        <div className="space-y-6">
            <div className="grid gap-5 sm:grid-cols-2">
                <WizardField
                    label="Pricing type"
                    htmlFor="pricing_type"
                    required
                    error={firstError(errors, 'pricing_type')}
                >
                    <select
                        id="pricing_type"
                        value={asString(data.pricing_type, 'fixed')}
                        onChange={(event) =>
                            updateField('pricing_type', event.target.value)
                        }
                        className="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    >
                        {pricingTypes.map((type) => (
                            <option key={type.value} value={type.value}>
                                {type.label}
                            </option>
                        ))}
                    </select>
                </WizardField>

                <WizardField
                    label="Price (USD)"
                    htmlFor="price"
                    required
                    error={firstError(errors, 'price')}
                    hint="Buyers see this amount before they request the service."
                >
                    <Input
                        id="price"
                        type="number"
                        min="5"
                        max="1000000"
                        step="0.01"
                        value={asString(data.price)}
                        onChange={(event) =>
                            updateField('price', event.target.value)
                        }
                        placeholder="1200"
                    />
                </WizardField>
            </div>

            <WizardField
                label="Currency"
                htmlFor="currency"
                required
                error={firstError(errors, 'currency')}
            >
                <select
                    id="currency"
                    value={asString(data.currency, 'USD')}
                    onChange={(event) =>
                        updateField('currency', event.target.value)
                    }
                    className="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 sm:max-w-xs"
                >
                    <option value="USD">USD — US dollar</option>
                </select>
            </WizardField>

            <WizardField
                label="What is included"
                htmlFor="includes"
                error={firstError(errors, 'includes')}
                hint="Optional. Mention anything that affects the price or scope."
            >
                <Textarea
                    id="includes"
                    value={asString(data.includes)}
                    onChange={(event) =>
                        updateField('includes', event.target.value)
                    }
                    rows={4}
                    maxLength={2000}
                    placeholder="API design, implementation, automated tests, and deployment notes."
                />
            </WizardField>
        </div>
    );
}
