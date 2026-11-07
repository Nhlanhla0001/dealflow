import type { MarketplaceCategory, WizardErrors } from '@/types';

export type StepProps = {
    data: Record<string, unknown>;
    errors: WizardErrors;
    categories: MarketplaceCategory[];
    updateField: (key: string, value: unknown) => void;
    updateFields: (values: Record<string, unknown>) => void;
};

export function asString(value: unknown, fallback = ''): string {
    if (typeof value === 'string') {
        return value;
    }

    if (typeof value === 'number') {
        return String(value);
    }

    return fallback;
}

export function firstError(
    errors: WizardErrors,
    ...keys: string[]
): string | undefined {
    for (const key of keys) {
        const value = errors[key];

        if (Array.isArray(value) && value.length > 0) {
            return value[0];
        }

        if (typeof value === 'string' && value !== '') {
            return value;
        }
    }

    return undefined;
}
