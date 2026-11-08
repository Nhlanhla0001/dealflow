import { formatMoney } from '@/lib/format';
import {
    WizardReview,
    type ReviewSection,
} from '@/components/wizard/wizard-review';
import { asString, type BuyerStepProps } from './types';

type Props = BuyerStepProps & {
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

export function ReviewStep({ data, services, onEdit }: Props) {
    const service = services.find(
        (item) => item.id === Number(data.service_id),
    );
    const deliverables = Array.isArray(data.required_deliverables)
        ? (data.required_deliverables as string[]).filter(Boolean)
        : [];

    const sections: ReviewSection[] = [
        {
            key: 'service',
            label: 'Service',
            items: [
                { label: 'Service', value: text(service?.title) },
                { label: 'Seller', value: text(service?.seller?.name) },
                {
                    label: 'Listed price',
                    value: service
                        ? formatMoney(service.price_minor, service.currency)
                        : '—',
                },
            ],
            onEdit: () => onEdit('service'),
        },
        {
            key: 'outcome',
            label: 'Outcome',
            items: [
                { label: 'Title', value: text(data.title) },
                {
                    label: 'Description',
                    value: (
                        <span className="whitespace-pre-line">
                            {text(data.description)}
                        </span>
                    ),
                },
                {
                    label: 'Deliverables',
                    value:
                        deliverables.length > 0 ? (
                            <ul className="list-disc space-y-1 pl-4">
                                {deliverables.map((item) => (
                                    <li key={item}>{item}</li>
                                ))}
                            </ul>
                        ) : (
                            '—'
                        ),
                },
            ],
            onEdit: () => onEdit('outcome'),
        },
        {
            key: 'budget',
            label: 'Budget',
            items: [
                {
                    label: 'Maximum budget',
                    value: formatMoney(
                        Math.round(Number(data.budget ?? 0) * 100),
                        asString(data.currency, 'USD'),
                    ),
                },
                {
                    label: 'Preferred delivery',
                    value: `${text(data.delivery_days)} days`,
                },
            ],
            onEdit: () => onEdit('budget'),
        },
        {
            key: 'conditions',
            label: 'Conditions',
            items: [
                {
                    label: 'Essential',
                    value: text(data.essential_requirements),
                },
                {
                    label: 'Negotiable',
                    value: text(data.negotiable_conditions),
                },
            ],
            onEdit: () => onEdit('conditions'),
        },
        {
            key: 'negotiation',
            label: 'Negotiation',
            items: [
                {
                    label: 'Mode',
                    value:
                        data.negotiation_mode === 'ai'
                            ? 'AI-assisted'
                            : 'Manual',
                },
            ],
            onEdit: () => onEdit('negotiation'),
        },
    ];

    return (
        <div className="space-y-6">
            <div className="rounded-xl border border-border p-5">
                <p className="text-xs text-muted-foreground">
                    {service?.category?.name ?? 'Service request'}
                </p>
                <h3 className="mt-2 text-xl font-semibold tracking-tight">
                    {text(data.title)}
                </h3>
                <p className="mt-2 text-sm leading-6 whitespace-pre-line text-muted-foreground">
                    {text(data.description)}
                </p>
                <div className="mt-4 flex flex-wrap gap-4 text-sm">
                    <span className="font-semibold">
                        {formatMoney(
                            Math.round(Number(data.budget ?? 0) * 100),
                            asString(data.currency, 'USD'),
                        )}{' '}
                        max
                    </span>
                    <span className="text-muted-foreground">
                        {text(data.delivery_days)} day delivery
                    </span>
                    <span className="text-muted-foreground">
                        {data.negotiation_mode === 'ai'
                            ? 'AI-assisted'
                            : 'Manual'}
                    </span>
                </div>
            </div>

            <WizardReview sections={sections} />
        </div>
    );
}
