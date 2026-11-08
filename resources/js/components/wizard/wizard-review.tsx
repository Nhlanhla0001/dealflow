import { Pencil } from 'lucide-react';
import { Button } from '@/components/ui/button';

export type ReviewSection = {
    key: string;
    label: string;
    items: { label: string; value: React.ReactNode }[];
    onEdit?: () => void;
};

export function WizardReview({ sections }: { sections: ReviewSection[] }) {
    return (
        <div className="space-y-6">
            {sections.map((section) => (
                <section
                    key={section.key}
                    className="rounded-xl border border-border bg-card"
                >
                    <div className="flex items-center justify-between gap-3 border-b border-border px-5 py-3.5">
                        <h3 className="text-sm font-semibold">
                            {section.label}
                        </h3>
                        {section.onEdit && (
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                onClick={section.onEdit}
                            >
                                <Pencil className="size-3.5" aria-hidden />
                                Edit
                            </Button>
                        )}
                    </div>
                    <dl className="divide-y divide-border">
                        {section.items.map((item) => (
                            <div
                                key={item.label}
                                className="grid gap-1 px-5 py-3 sm:grid-cols-[11rem_1fr] sm:gap-4"
                            >
                                <dt className="text-xs text-muted-foreground">
                                    {item.label}
                                </dt>
                                <dd className="text-sm break-words">
                                    {item.value}
                                </dd>
                            </div>
                        ))}
                    </dl>
                </section>
            ))}
        </div>
    );
}
