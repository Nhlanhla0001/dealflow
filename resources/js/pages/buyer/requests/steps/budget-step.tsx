import { Input } from '@/components/ui/input';
import { WizardField } from '@/components/wizard/wizard-field';
import { asString, firstError, type BuyerStepProps } from './types';

export function BudgetStep({ data, errors, updateField }: BuyerStepProps) {
    return (
        <div className="space-y-6">
            <div className="grid gap-5 sm:grid-cols-2">
                <WizardField
                    label="Maximum budget (USD)"
                    htmlFor="budget"
                    required
                    error={firstError(errors, 'budget')}
                    hint="This is private. The seller agent never receives your maximum unless you reveal it."
                >
                    <Input
                        id="budget"
                        type="number"
                        min="5"
                        max="1000000"
                        step="0.01"
                        value={asString(data.budget)}
                        onChange={(event) =>
                            updateField('budget', event.target.value)
                        }
                        placeholder="500"
                    />
                </WizardField>

                <WizardField
                    label="Preferred delivery (days)"
                    htmlFor="delivery_days"
                    required
                    error={firstError(errors, 'delivery_days')}
                >
                    <Input
                        id="delivery_days"
                        type="number"
                        min="1"
                        max="365"
                        value={asString(data.delivery_days)}
                        onChange={(event) =>
                            updateField('delivery_days', event.target.value)
                        }
                        placeholder="7"
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

            <div className="rounded-lg border border-border bg-muted/40 p-4 text-sm leading-6 text-muted-foreground">
                The seller sees the request and an opening offer based on the
                service listing. Your private maximum is enforced by the buyer
                agent and the server, not by a prompt.
            </div>
        </div>
    );
}
