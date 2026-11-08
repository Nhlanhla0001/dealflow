import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { WizardField } from '@/components/wizard/wizard-field';
import { asString, firstError, type StepProps } from './types';

export function DeliveryStep({ data, errors, updateField }: StepProps) {
    return (
        <div className="space-y-6">
            <div className="grid gap-5 sm:grid-cols-2">
                <WizardField
                    label="Delivery time (days)"
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
                        placeholder="14"
                    />
                </WizardField>

                <WizardField
                    label="Revision rounds"
                    htmlFor="revisions"
                    required
                    error={firstError(errors, 'revisions')}
                    hint="How many revision rounds are included in the price."
                >
                    <Input
                        id="revisions"
                        type="number"
                        min="0"
                        max="20"
                        value={asString(data.revisions, '0')}
                        onChange={(event) =>
                            updateField('revisions', event.target.value)
                        }
                    />
                </WizardField>
            </div>

            <WizardField
                label="What is not included"
                htmlFor="exclusions"
                error={firstError(errors, 'exclusions')}
                hint="Optional. Set expectations before the work starts."
            >
                <Textarea
                    id="exclusions"
                    value={asString(data.exclusions)}
                    onChange={(event) =>
                        updateField('exclusions', event.target.value)
                    }
                    rows={4}
                    maxLength={2000}
                    placeholder="Content writing, third-party licence fees, and ongoing support are not included."
                />
            </WizardField>

            <WizardField
                label="What you need from the buyer"
                htmlFor="requirements"
                error={firstError(errors, 'requirements')}
                hint="Optional. Access, assets, or decisions the buyer must provide."
            >
                <Textarea
                    id="requirements"
                    value={asString(data.requirements)}
                    onChange={(event) =>
                        updateField('requirements', event.target.value)
                    }
                    rows={4}
                    maxLength={3000}
                    placeholder="A short brief, brand assets, and one decision-maker for scope questions."
                />
            </WizardField>
        </div>
    );
}
