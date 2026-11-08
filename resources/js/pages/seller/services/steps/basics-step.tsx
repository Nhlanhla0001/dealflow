import { Plus, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { WizardField } from '@/components/wizard/wizard-field';
import { asString, firstError, type StepProps } from './types';

export function BasicsStep({ data, errors, updateField }: StepProps) {
    const savedDeliverables = Array.isArray(data.deliverables)
        ? (data.deliverables as string[])
        : [];
    const deliverables =
        savedDeliverables.length > 0 ? savedDeliverables : [''];

    const updateDeliverable = (index: number, value: string) => {
        const next = [...deliverables];
        next[index] = value;
        updateField('deliverables', next);
    };

    const removeDeliverable = (index: number) => {
        updateField(
            'deliverables',
            deliverables.filter((_, itemIndex) => itemIndex !== index),
        );
    };

    return (
        <div className="space-y-6">
            <WizardField
                label="Service title"
                htmlFor="title"
                required
                error={firstError(errors, 'title')}
                hint="Name the outcome, not the process. Example: 'Laravel API and dashboard build'."
            >
                <Input
                    id="title"
                    value={asString(data.title)}
                    onChange={(event) =>
                        updateField('title', event.target.value)
                    }
                    maxLength={120}
                    placeholder="Laravel API and dashboard build"
                    autoComplete="off"
                />
            </WizardField>

            <WizardField
                label="Description"
                htmlFor="description"
                required
                error={firstError(errors, 'description')}
                hint="Explain the problem, the outcome, and who this is for."
            >
                <Textarea
                    id="description"
                    value={asString(data.description)}
                    onChange={(event) =>
                        updateField('description', event.target.value)
                    }
                    rows={7}
                    maxLength={5000}
                    placeholder="A production-ready Laravel API, authentication, and a focused React dashboard for your internal workflow."
                />
            </WizardField>

            <div className="space-y-2">
                <div className="flex items-center justify-between gap-3">
                    <div>
                        <p className="text-sm font-medium">
                            Deliverables
                            <span className="ml-1 text-destructive" aria-hidden>
                                *
                            </span>
                        </p>
                        <p className="text-xs text-muted-foreground">
                            Concrete things the buyer receives.
                        </p>
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() =>
                            updateField('deliverables', [...deliverables, ''])
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
                                onClick={() => removeDeliverable(index)}
                                disabled={deliverables.length === 1}
                                aria-label={`Remove deliverable ${index + 1}`}
                            >
                                <X className="size-4" aria-hidden />
                            </Button>
                        </div>
                    ))}
                </div>
                {firstError(errors, 'deliverables', 'deliverables.0') && (
                    <p className="text-sm text-destructive">
                        {firstError(errors, 'deliverables', 'deliverables.0')}
                    </p>
                )}
            </div>
        </div>
    );
}
