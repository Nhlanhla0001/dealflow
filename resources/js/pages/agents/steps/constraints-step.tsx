import { Input } from '@/components/ui/input';
import { WizardField } from '@/components/wizard/wizard-field';
import { asString, firstError, type AgentStepProps } from './types';

export function ConstraintsStep({ data, errors, updateField }: AgentStepProps) {
    const isSeller = data.role === 'seller';

    return (
        <div className="space-y-6">
            <div className="rounded-lg border border-border bg-muted/40 p-4 text-sm leading-6 text-muted-foreground">
                These limits are private to your side. They are enforced on the
                server and are never included in the other party's agent
                context.
            </div>

            {isSeller ? (
                <WizardField
                    label="Private floor (USD)"
                    htmlFor="floor"
                    required
                    error={firstError(errors, 'floor')}
                    hint="The lowest price your agent may accept. It cannot be above your opening offer."
                >
                    <Input
                        id="floor"
                        type="number"
                        min="1"
                        max="1000000"
                        step="0.01"
                        value={asString(data.floor)}
                        onChange={(event) =>
                            updateField('floor', event.target.value)
                        }
                        placeholder="450"
                    />
                </WizardField>
            ) : (
                <WizardField
                    label="Private maximum budget (USD)"
                    htmlFor="max_budget"
                    required
                    error={firstError(errors, 'max_budget')}
                    hint="The highest price your agent may accept. It cannot exceed the transaction budget."
                >
                    <Input
                        id="max_budget"
                        type="number"
                        min="1"
                        max="1000000"
                        step="0.01"
                        value={asString(data.max_budget)}
                        onChange={(event) =>
                            updateField('max_budget', event.target.value)
                        }
                        placeholder="500"
                    />
                </WizardField>
            )}

            <WizardField
                label="Maximum delivery window (days)"
                htmlFor="max_delivery_days"
                required
                error={firstError(errors, 'max_delivery_days')}
            >
                <Input
                    id="max_delivery_days"
                    type="number"
                    min="1"
                    max="365"
                    value={asString(data.max_delivery_days)}
                    onChange={(event) =>
                        updateField('max_delivery_days', event.target.value)
                    }
                />
            </WizardField>

            <p className="text-sm leading-6 text-muted-foreground">
                The final agreement still needs your explicit approval. The
                agent cannot approve or pay.
            </p>
        </div>
    );
}
