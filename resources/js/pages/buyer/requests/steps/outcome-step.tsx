import { Plus, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { WizardField } from '@/components/wizard/wizard-field';
import { asString, firstError, type BuyerStepProps } from './types';

export function OutcomeStep({ data, errors, updateField }: BuyerStepProps) {
    const savedDeliverables = Array.isArray(data.required_deliverables)
        ? (data.required_deliverables as string[])
        : [];
    const deliverables =
        savedDeliverables.length > 0 ? savedDeliverables : [''];

    const updateDeliverable = (index: number, value: string) => {
        const next = [...deliverables];
        next[index] = value;
        updateField('required_deliverables', next);
    };

    return (
        <div className="space-y-6">
            <WizardField
                label="Request title"
                htmlFor="title"
                required
                error={firstError(errors, 'title')}
            >
                <Input
                    id="title"
                    value={asString(data.title)}
                    onChange={(event) =>
                        updateField('title', event.target.value)
                    }
                    maxLength={120}
                    placeholder="Product launch landing page"
                />
            </WizardField>

            <WizardField
                label="Desired outcome"
                htmlFor="description"
                required
                error={firstError(errors, 'description')}
                hint="Explain the result, context, and what success looks like."
            >
                <Textarea
                    id="description"
                    value={asString(data.description)}
                    onChange={(event) =>
                        updateField('description', event.target.value)
                    }
                    rows={7}
                    maxLength={2000}
                    placeholder="A launch page that explains the product, captures waitlist signups, and is ready to publish."
                />
            </WizardField>

            <div className="space-y-2">
                <div className="flex items-center justify-between gap-3">
                    <div>
                        <p className="text-sm font-medium">
                            Required deliverables
                            <span className="ml-1 text-destructive" aria-hidden>
                                *
                            </span>
                        </p>
                        <p className="text-xs text-muted-foreground">
                            The concrete outputs you expect.
                        </p>
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() =>
                            updateField('required_deliverables', [
                                ...deliverables,
                                '',
                            ])
                        }
                        disabled={deliverables.length >= 12}
                    >
                        <Plus className="size-3.5" aria-hidden />
                        Add
                    </Button>
                </div>

                <div className="space-y-2">
                    {deliverables.map((deliverable, index) => (
                        <div key={index} className="flex items-center gap-2">
                            <Input
                                value={deliverable}
                                onChange={(event) =>
                                    updateDeliverable(index, event.target.value)
                                }
                                placeholder={`Deliverable ${index + 1}`}
                                maxLength={200}
                                aria-label={`Deliverable ${index + 1}`}
                            />
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                onClick={() =>
                                    updateField(
                                        'required_deliverables',
                                        deliverables.filter(
                                            (_, itemIndex) =>
                                                itemIndex !== index,
                                        ),
                                    )
                                }
                                disabled={deliverables.length === 1}
                                aria-label={`Remove deliverable ${index + 1}`}
                            >
                                <X className="size-4" aria-hidden />
                            </Button>
                        </div>
                    ))}
                </div>
                {firstError(
                    errors,
                    'required_deliverables',
                    'required_deliverables.0',
                ) && (
                    <p className="text-sm text-destructive">
                        {firstError(
                            errors,
                            'required_deliverables',
                            'required_deliverables.0',
                        )}
                    </p>
                )}
            </div>
        </div>
    );
}
