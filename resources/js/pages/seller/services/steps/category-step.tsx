import { WizardField } from '@/components/wizard/wizard-field';
import { asString, firstError, type StepProps } from './types';

export function CategoryStep({
    data,
    errors,
    categories,
    updateField,
}: StepProps) {
    const selected = categories.find(
        (category) => category.id === Number(data.category_id),
    );

    return (
        <div className="space-y-5">
            <WizardField
                label="Category"
                htmlFor="category_id"
                required
                error={firstError(errors, 'category_id')}
                hint="Pick the closest fit. Buyers filter the marketplace by category."
            >
                <select
                    id="category_id"
                    value={asString(data.category_id)}
                    onChange={(event) =>
                        updateField(
                            'category_id',
                            event.target.value
                                ? Number(event.target.value)
                                : '',
                        )
                    }
                    className="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                >
                    <option value="">Choose a category</option>
                    {categories.map((category) => (
                        <option key={category.id} value={category.id}>
                            {category.name}
                        </option>
                    ))}
                </select>
            </WizardField>

            {selected?.description && (
                <div className="rounded-lg border border-border bg-muted/40 p-4">
                    <p className="text-xs font-medium text-muted-foreground">
                        About this category
                    </p>
                    <p className="mt-1 text-sm">{selected.description}</p>
                </div>
            )}
        </div>
    );
}
