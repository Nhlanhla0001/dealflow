import { Bot, UserRound } from 'lucide-react';
import { firstError, type BuyerStepProps } from './types';

const options = [
    {
        value: 'ai',
        label: 'AI-assisted negotiation',
        description:
            'Your buyer agent negotiates inside your private budget. The seller must activate their own agent before negotiation starts.',
        icon: Bot,
    },
    {
        value: 'manual',
        label: 'Manual negotiation',
        description:
            'You and the seller exchange offers yourselves. No agent runs on your behalf.',
        icon: UserRound,
    },
] as const;

export function NegotiationStep({ data, errors, updateField }: BuyerStepProps) {
    return (
        <div className="space-y-3">
            {options.map((option) => {
                const Icon = option.icon;
                const selected = data.negotiation_mode === option.value;

                return (
                    <label
                        key={option.value}
                        className={`flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition-colors ${
                            selected
                                ? 'border-foreground bg-muted/40'
                                : 'border-border hover:bg-muted/30'
                        }`}
                    >
                        <input
                            type="radio"
                            name="negotiation_mode"
                            value={option.value}
                            checked={selected}
                            onChange={() =>
                                updateField('negotiation_mode', option.value)
                            }
                            className="mt-0.5 size-4 accent-emerald-700"
                        />
                        <Icon
                            className="mt-0.5 size-4 shrink-0 text-muted-foreground"
                            aria-hidden
                        />
                        <span>
                            <span className="block text-sm font-medium">
                                {option.label}
                            </span>
                            <span className="mt-1 block text-sm leading-6 text-muted-foreground">
                                {option.description}
                            </span>
                        </span>
                    </label>
                );
            })}

            {firstError(errors, 'negotiation_mode') && (
                <p className="text-sm text-destructive">
                    {firstError(errors, 'negotiation_mode')}
                </p>
            )}
        </div>
    );
}
