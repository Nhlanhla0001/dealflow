import { Check } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { WizardStepDefinition } from '@/types';

type Props = {
    steps: WizardStepDefinition[];
    currentStep: string;
    completedSteps: string[];
    onStepClick?: (key: string) => void;
};

export function WizardProgress({
    steps,
    currentStep,
    completedSteps,
    onStepClick,
}: Props) {
    const currentIndex = steps.findIndex((step) => step.key === currentStep);
    const current = steps[currentIndex] ?? steps[0];

    return (
        <>
            <div className="md:hidden">
                <div className="flex items-center justify-between gap-3 text-xs">
                    <span className="font-medium">
                        Step {currentIndex + 1} of {steps.length}
                    </span>
                    <span className="text-muted-foreground">
                        {current?.label}
                    </span>
                </div>
                <div className="mt-2 h-1.5 overflow-hidden rounded-full bg-muted">
                    <div
                        className="h-full rounded-full bg-emerald-700 transition-[width] dark:bg-emerald-400"
                        style={{
                            width: `${Math.round(((currentIndex + 1) / steps.length) * 100)}%`,
                        }}
                    />
                </div>
            </div>

            <ol
                className="hidden auto-cols-fr grid-flow-col gap-1 md:grid"
                aria-label="Wizard progress"
            >
                {steps.map((step, index) => {
                    const isComplete = completedSteps.includes(step.key);
                    const isCurrent = step.key === currentStep;
                    const canOpen =
                        isComplete || isCurrent || index < currentIndex;
                    const content = (
                        <>
                            <span
                                className={cn(
                                    'flex size-6 shrink-0 items-center justify-center rounded-full border text-[10px] font-semibold',
                                    isComplete &&
                                        'border-emerald-700 bg-emerald-700 text-white dark:border-emerald-400 dark:bg-emerald-400 dark:text-emerald-950',
                                    isCurrent &&
                                        !isComplete &&
                                        'border-foreground bg-foreground text-background',
                                    !isComplete &&
                                        !isCurrent &&
                                        'border-border text-muted-foreground',
                                )}
                            >
                                {isComplete ? (
                                    <Check className="size-3" aria-hidden />
                                ) : (
                                    index + 1
                                )}
                            </span>
                            <span
                                className={cn(
                                    'truncate text-[11px]',
                                    isCurrent
                                        ? 'font-medium text-foreground'
                                        : 'text-muted-foreground',
                                )}
                            >
                                {step.label}
                            </span>
                        </>
                    );

                    return (
                        <li key={step.key} className="min-w-0">
                            {onStepClick && canOpen ? (
                                <button
                                    type="button"
                                    onClick={() => onStepClick(step.key)}
                                    aria-current={
                                        isCurrent ? 'step' : undefined
                                    }
                                    className="flex w-full items-center gap-1.5 rounded-md px-1.5 py-2 text-left transition-colors hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                >
                                    {content}
                                </button>
                            ) : (
                                <div
                                    aria-current={
                                        isCurrent ? 'step' : undefined
                                    }
                                    className="flex items-center gap-1.5 px-1.5 py-2"
                                >
                                    {content}
                                </div>
                            )}
                        </li>
                    );
                })}
            </ol>
        </>
    );
}
