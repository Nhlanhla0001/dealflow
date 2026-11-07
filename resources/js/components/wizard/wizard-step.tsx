import { useEffect, useRef } from 'react';

type Props = {
    title: string;
    description: string;
    stepNumber: number;
    totalSteps: number;
    children: React.ReactNode;
};

export function WizardStep({
    title,
    description,
    stepNumber,
    totalSteps,
    children,
}: Props) {
    const headingRef = useRef<HTMLHeadingElement>(null);

    useEffect(() => {
        headingRef.current?.focus();
    }, [title]);

    return (
        <section aria-labelledby="wizard-step-title" className="min-w-0">
            <div className="mb-6">
                <p className="font-mono text-[11px] text-muted-foreground">
                    Step {stepNumber} of {totalSteps}
                </p>
                <h2
                    id="wizard-step-title"
                    ref={headingRef}
                    tabIndex={-1}
                    className="mt-2 text-xl font-semibold tracking-tight outline-none"
                >
                    {title}
                </h2>
                <p className="mt-1.5 text-sm leading-6 text-muted-foreground">
                    {description}
                </p>
            </div>
            {children}
        </section>
    );
}
