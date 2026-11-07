import { WizardProgress } from '@/components/wizard/wizard-progress';
import type { WizardStepDefinition } from '@/types';

type Props = {
    title: string;
    description: string;
    steps: WizardStepDefinition[];
    currentStep: string;
    completedSteps: string[];
    onStepClick?: (key: string) => void;
    children: React.ReactNode;
};

export function WizardLayout({
    title,
    description,
    steps,
    currentStep,
    completedSteps,
    onStepClick,
    children,
}: Props) {
    return (
        <div className="mx-auto w-full max-w-4xl">
            <div className="mb-6">
                <p className="text-xs font-medium tracking-[0.22em] text-muted-foreground uppercase">
                    Seller workspace
                </p>
                <h1 className="mt-2 text-2xl font-semibold tracking-tight">
                    {title}
                </h1>
                <p className="mt-1.5 max-w-2xl text-sm leading-6 text-muted-foreground">
                    {description}
                </p>
            </div>

            <WizardProgress
                steps={steps}
                currentStep={currentStep}
                completedSteps={completedSteps}
                onStepClick={onStepClick}
            />

            <div className="mt-8 rounded-xl border border-border bg-card p-5 sm:p-6">
                {children}
            </div>
        </div>
    );
}
