import { CheckCircle2, XCircle } from 'lucide-react';
import { WizardField } from '@/components/wizard/wizard-field';
import { asString, firstError, type AgentStepProps } from './types';

export function ProviderStep({
    data,
    errors,
    providers,
    updateField,
}: AgentStepProps) {
    const providerId = asString(data.provider, 'gemini');
    const provider = providers[providerId];

    return (
        <div className="space-y-5">
            <WizardField
                label="Provider"
                htmlFor="provider"
                required
                error={firstError(errors, 'provider')}
            >
                <select
                    id="provider"
                    value={providerId}
                    onChange={(event) => {
                        const next = event.target.value;
                        updateField('provider', next);
                        const firstModel = providers[next]?.models[0]?.id;

                        if (firstModel) {
                            updateField('model', firstModel);
                        }
                    }}
                    className="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                >
                    {Object.entries(providers).map(([id, item]) => (
                        <option key={id} value={id}>
                            {item.label}
                            {item.configured ? '' : ' — not configured'}
                        </option>
                    ))}
                </select>
            </WizardField>

            {provider && (
                <div className="flex items-center gap-2 text-sm">
                    {provider.configured ? (
                        <>
                            <CheckCircle2
                                className="size-4 text-emerald-700 dark:text-emerald-400"
                                aria-hidden
                            />
                            Provider credentials are configured.
                        </>
                    ) : (
                        <>
                            <XCircle
                                className="size-4 text-destructive"
                                aria-hidden
                            />
                            Provider credentials are missing. Activation will
                            fail until they are configured.
                        </>
                    )}
                </div>
            )}

            <WizardField
                label="Model"
                htmlFor="model"
                required
                error={firstError(errors, 'model')}
            >
                <select
                    id="model"
                    value={asString(data.model)}
                    onChange={(event) =>
                        updateField('model', event.target.value)
                    }
                    className="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                >
                    {(provider?.models ?? []).map((model) => (
                        <option key={model.id} value={model.id}>
                            {model.label}
                        </option>
                    ))}
                </select>
            </WizardField>

            <p className="text-sm leading-6 text-muted-foreground">
                The model choice is stored per participant. The buyer and seller
                can use different providers and models on the same transaction.
            </p>
        </div>
    );
}
