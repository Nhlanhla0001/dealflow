import InputError from '@/components/input-error';

type Props = {
    label: string;
    htmlFor: string;
    error?: string | string[];
    hint?: string;
    required?: boolean;
    children: React.ReactNode;
};

export function WizardField({
    label,
    htmlFor,
    error,
    hint,
    required = false,
    children,
}: Props) {
    const message = Array.isArray(error) ? error[0] : error;

    return (
        <div className="grid gap-2">
            <label htmlFor={htmlFor} className="text-sm font-medium">
                {label}
                {required && (
                    <span className="ml-1 text-destructive" aria-hidden>
                        *
                    </span>
                )}
            </label>
            {children}
            {hint && <p className="text-xs text-muted-foreground">{hint}</p>}
            <InputError message={message} />
        </div>
    );
}
