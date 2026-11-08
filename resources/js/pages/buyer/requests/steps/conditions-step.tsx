import { Textarea } from '@/components/ui/textarea';
import { WizardField } from '@/components/wizard/wizard-field';
import { asString, firstError, type BuyerStepProps } from './types';

export function ConditionsStep({ data, errors, updateField }: BuyerStepProps) {
    return (
        <div className="space-y-6">
            <WizardField
                label="Essential requirements"
                htmlFor="essential_requirements"
                error={firstError(errors, 'essential_requirements')}
                hint="What must be true for the work to be acceptable?"
            >
                <Textarea
                    id="essential_requirements"
                    value={asString(data.essential_requirements)}
                    onChange={(event) =>
                        updateField(
                            'essential_requirements',
                            event.target.value,
                        )
                    }
                    rows={5}
                    maxLength={3000}
                    placeholder="Mobile and desktop layouts, accessible forms, and a working CMS."
                />
            </WizardField>

            <WizardField
                label="Negotiable conditions"
                htmlFor="negotiable_conditions"
                error={firstError(errors, 'negotiable_conditions')}
                hint="What can the seller propose differently?"
            >
                <Textarea
                    id="negotiable_conditions"
                    value={asString(data.negotiable_conditions)}
                    onChange={(event) =>
                        updateField('negotiable_conditions', event.target.value)
                    }
                    rows={5}
                    maxLength={3000}
                    placeholder="The delivery date can move by a few days. The revision count is open to discussion."
                />
            </WizardField>
        </div>
    );
}
