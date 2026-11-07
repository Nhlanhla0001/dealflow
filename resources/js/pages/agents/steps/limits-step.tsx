import { Input } from '@/components/ui/input';
import { formatMoney } from '@/lib/format';
import { WizardField } from '@/components/wizard/wizard-field';
import { asString, firstError, type AgentStepProps } from './types';

export function LimitsStep({
    data,
    errors,
    balanceMinor,
    updateField,
}: AgentStepProps) {
    return (
        <div className="space-y-6">
            <div className="grid gap-5 sm:grid-cols-2">
                <WizardField
                    label="Maximum negotiation turns"
                    htmlFor="max_turns"
                    required
                    error={firstError(errors, 'max_turns')}
                >
                    <Input
                        id="max_turns"
                        type="number"
                        min="1"
                        max="10"
                        value={asString(data.max_turns)}
                        onChange={(event) =>
                            updateField('max_turns', event.target.value)
                        }
                    />
                </WizardField>

                <WizardField
                    label="AI spending limit (USD)"
                    htmlFor="spending_limit"
                    required
                    error={firstError(errors, 'spending_limit')}
                    hint="The maximum AI usage this agent may consume. It is separate from the deal price."
                >
                    <Input
                        id="spending_limit"
                        type="number"
                        min="0"
                        max="1000"
                        step="0.01"
                        value={asString(data.spending_limit)}
                        onChange={(event) =>
                            updateField('spending_limit', event.target.value)
                        }
                    />
                </WizardField>
            </div>

            <WizardField
                label="AI billing method"
                htmlFor="billing_method"
                required
                error={firstError(errors, 'billing_method')}
            >
                <select
                    id="billing_method"
                    value={asString(data.billing_method, 'prepaid_credits')}
                    onChange={(event) =>
                        updateField('billing_method', event.target.value)
                    }
                    className="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 sm:max-w-md"
                >
                    <option value="prepaid_credits">Prepaid AI credits</option>
                </select>
            </WizardField>

            <div className="rounded-lg border border-border bg-muted/40 p-4 text-sm">
                <p className="font-medium">
                    Available AI credit: {formatMoney(balanceMinor)}
                </p>
                <p className="mt-1 leading-6 text-muted-foreground">
                    Activation fails if the spending limit is above the
                    available balance. Per-call usage metering is the next step;
                    the balance is already enforced at activation.
                </p>
            </div>
        </div>
    );
}
