import { AlertCircle, Check } from 'lucide-react';
import type { WizardErrors } from '@/types';

type Props = {
    errors: WizardErrors;
};

export function WizardValidationSummary({ errors }: Props) {
    const messages = Object.entries(errors).flatMap(([field, value]) => {
        const items = Array.isArray(value) ? value : [value];

        return items.map((message) => ({ field, message }));
    });

    if (messages.length === 0) {
        return null;
    }

    return (
        <div
            role="alert"
            className="mb-6 rounded-lg border border-destructive/30 bg-destructive/5 p-4"
        >
            <p className="flex items-center gap-2 text-sm font-medium text-destructive">
                <AlertCircle className="size-4" aria-hidden />
                Please check the highlighted fields.
            </p>
            <ul className="mt-2 list-disc space-y-1 pl-5 text-sm text-destructive">
                {messages.map((item, index) => (
                    <li key={`${item.field}-${index}`}>{item.message}</li>
                ))}
            </ul>
        </div>
    );
}

export function WizardSavedState({ state }: { state: string }) {
    if (state === 'idle') {
        return null;
    }

    if (state === 'saving') {
        return <span className="text-xs text-muted-foreground">Saving…</span>;
    }

    if (state === 'saved') {
        return (
            <span className="inline-flex items-center gap-1 text-xs text-emerald-700 dark:text-emerald-400">
                <Check className="size-3.5" aria-hidden />
                Saved
            </span>
        );
    }

    return (
        <span className="text-xs text-destructive">
            Not saved. Check the errors above.
        </span>
    );
}
