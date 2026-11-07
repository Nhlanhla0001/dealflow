import { formatMoney } from '@/lib/format';
import {
    WizardReview,
    type ReviewSection,
} from '@/components/wizard/wizard-review';
import { asString, type AgentStepProps } from './types';

type Props = AgentStepProps & {
    onEdit: (key: string) => void;
};

function text(value: unknown): string {
    if (typeof value === 'string') {
        return value === '' ? '—' : value;
    }

    if (typeof value === 'number') {
        return String(value);
    }

    return '—';
}

export function ReviewStep({ data, providers, balanceMinor, onEdit }: Props) {
    const provider = providers[asString(data.provider)];
    const isSeller = data.role === 'seller';

    const sections: ReviewSection[] = [
        {
            key: 'provider',
            label: 'Provider',
            items: [
                { label: 'Provider', value: text(provider?.label) },
                { label: 'Model', value: text(data.model) },
            ],
            onEdit: () => onEdit('provider'),
        },
        {
            key: 'objective',
            label: 'Objective',
            items: [
                {
                    label: 'Objective',
                    value: (
                        <span className="whitespace-pre-line">
                            {text(data.objective)}
                        </span>
                    ),
                },
            ],
            onEdit: () => onEdit('objective'),
        },
        {
            key: 'constraints',
            label: 'Private constraints',
            items: [
                {
                    label: isSeller ? 'Private floor' : 'Private maximum',
                    value: isSeller
                        ? formatMoney(Math.round(Number(data.floor ?? 0) * 100))
                        : formatMoney(
                              Math.round(Number(data.max_budget ?? 0) * 100),
                          ),
                },
                {
                    label: 'Maximum delivery',
                    value: `${text(data.max_delivery_days)} days`,
                },
            ],
            onEdit: () => onEdit('constraints'),
        },
        {
            key: 'limits',
            label: 'Limits and billing',
            items: [
                { label: 'Maximum turns', value: text(data.max_turns) },
                {
                    label: 'AI spending limit',
                    value: formatMoney(
                        Math.round(Number(data.spending_limit ?? 0) * 100),
                    ),
                },
                {
                    label: 'Available credit',
                    value: formatMoney(balanceMinor),
                },
                {
                    label: 'Billing method',
                    value: 'Prepaid AI credits',
                },
            ],
            onEdit: () => onEdit('limits'),
        },
    ];

    return (
        <div className="space-y-6">
            <div className="rounded-xl border border-border p-5">
                <p className="text-xs text-muted-foreground">
                    {isSeller ? 'Seller agent' : 'Buyer agent'}
                </p>
                <h3 className="mt-2 text-xl font-semibold tracking-tight">
                    {provider?.label ?? 'AI provider'} · {text(data.model)}
                </h3>
                <p className="mt-2 text-sm leading-6 whitespace-pre-line text-muted-foreground">
                    {text(data.objective)}
                </p>
            </div>

            <WizardReview sections={sections} />
        </div>
    );
}
