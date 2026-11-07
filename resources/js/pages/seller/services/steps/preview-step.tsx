import { formatMoney } from '@/lib/format';
import {
    WizardReview,
    type ReviewSection,
} from '@/components/wizard/wizard-review';
import { asString, type StepProps } from './types';

type Props = StepProps & {
    onEdit: (key: string) => void;
};

const pricingLabels: Record<string, string> = {
    fixed: 'Fixed price',
    hourly: 'Hourly',
    starting_at: 'Starting at',
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

export function PreviewStep({ data, categories, onEdit }: Props) {
    const category = categories.find(
        (item) => item.id === Number(data.category_id),
    );
    const deliverables = Array.isArray(data.deliverables)
        ? (data.deliverables as string[]).filter(Boolean)
        : [];
    const images = Array.isArray(data.image_urls)
        ? (data.image_urls as string[]).filter(Boolean)
        : [];

    const sections: ReviewSection[] = [
        {
            key: 'category',
            label: 'Category',
            items: [{ label: 'Category', value: text(category?.name) }],
            onEdit: () => onEdit('category'),
        },
        {
            key: 'basics',
            label: 'Service basics',
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
            onEdit: () => onEdit('basics'),
        },
        {
            key: 'pricing',
            label: 'Pricing',
            items: [
                {
                    label: 'Pricing type',
                    value:
                        pricingLabels[asString(data.pricing_type)] ??
                        'Fixed price',
                },
                {
                    label: 'Price',
                    value: formatMoney(
                        Math.round(Number(data.price ?? 0) * 100),
                        asString(data.currency, 'USD'),
                    ),
                },
                { label: 'Includes', value: text(data.includes) },
            ],
            onEdit: () => onEdit('pricing'),
        },
        {
            key: 'delivery',
            label: 'Delivery',
            items: [
                {
                    label: 'Delivery time',
                    value: `${text(data.delivery_days)} days`,
                },
                {
                    label: 'Revisions',
                    value: text(data.revisions),
                },
                { label: 'Not included', value: text(data.exclusions) },
                { label: 'Buyer requirements', value: text(data.requirements) },
            ],
            onEdit: () => onEdit('delivery'),
        },
        {
            key: 'media',
            label: 'Proof and media',
            items: [
                {
                    label: 'Images',
                    value:
                        images.length > 0
                            ? `${images.length} image URL${images.length === 1 ? '' : 's'}`
                            : 'None',
                },
                {
                    label: 'Supporting information',
                    value: text(data.supporting_info),
                },
            ],
            onEdit: () => onEdit('media'),
        },
        {
            key: 'negotiation',
            label: 'Negotiation',
            items: [
                {
                    label: 'Buyer negotiation',
                    value: data.negotiation_enabled ? 'Allowed' : 'Not allowed',
                },
            ],
            onEdit: () => onEdit('negotiation'),
        },
    ];

    return (
        <div className="space-y-6">
            <div className="rounded-xl border border-border p-5">
                <div className="flex flex-wrap items-center justify-between gap-2 text-xs text-muted-foreground">
                    <span>{category?.name ?? 'Service'}</span>
                    <span>
                        {data.negotiation_enabled
                            ? 'Negotiable'
                            : 'Fixed terms'}
                    </span>
                </div>
                <h3 className="mt-3 text-xl font-semibold tracking-tight">
                    {text(data.title)}
                </h3>
                <p className="mt-2 text-sm leading-6 whitespace-pre-line text-muted-foreground">
                    {text(data.description)}
                </p>
                <div className="mt-4 flex flex-wrap items-center gap-4 text-sm">
                    <span className="font-semibold">
                        {formatMoney(
                            Math.round(Number(data.price ?? 0) * 100),
                            asString(data.currency, 'USD'),
                        )}
                    </span>
                    <span className="text-muted-foreground">
                        {text(data.delivery_days)} day delivery
                    </span>
                    <span className="text-muted-foreground">
                        {text(data.revisions)} revision
                        {Number(data.revisions) === 1 ? '' : 's'}
                    </span>
                </div>
                {deliverables.length > 0 && (
                    <ul className="mt-4 grid gap-1.5 text-sm sm:grid-cols-2">
                        {deliverables.map((item) => (
                            <li key={item} className="flex items-start gap-2">
                                <span
                                    className="mt-2 size-1.5 shrink-0 rounded-full bg-emerald-700 dark:bg-emerald-400"
                                    aria-hidden
                                />
                                {item}
                            </li>
                        ))}
                    </ul>
                )}
            </div>

            <WizardReview sections={sections} />
        </div>
    );
}
