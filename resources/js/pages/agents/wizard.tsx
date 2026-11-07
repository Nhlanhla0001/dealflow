import { Head, Link, router } from '@inertiajs/react';
import { AlertCircle, ArrowLeft } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { WizardConfirmDialog } from '@/components/wizard/wizard-confirm-dialog';
import { WizardLayout } from '@/components/wizard/wizard-layout';
import { WizardNavigation } from '@/components/wizard/wizard-navigation';
import { WizardStep } from '@/components/wizard/wizard-step';
import { WizardValidationSummary } from '@/components/wizard/wizard-validation-summary';
import { useWizard } from '@/hooks/use-wizard';
import { ConstraintsStep } from '@/pages/agents/steps/constraints-step';
import { LimitsStep } from '@/pages/agents/steps/limits-step';
import { ObjectiveStep } from '@/pages/agents/steps/objective-step';
import { ProviderStep } from '@/pages/agents/steps/provider-step';
import { PublishStep } from '@/pages/agents/steps/publish-step';
import { ReviewStep } from '@/pages/agents/steps/review-step';
import type { AgentProvider } from '@/pages/agents/steps/types';
import type { WizardDraft } from '@/types';

type Props = {
    draft: WizardDraft;
    transaction: {
        id: number;
        title: string;
        budget_minor: number;
        delivery_days: number;
    };
    providers: Record<string, AgentProvider>;
    balanceMinor: number;
    backUrl: string;
};

export default function AgentWizard({
    draft,
    transaction,
    providers,
    balanceMinor,
    backUrl,
}: Props) {
    const wizard = useWizard({
        initialDraft: draft,
        servicesUrl: backUrl,
        endpoints: {
            update: `/wizard-drafts/${draft.uuid}`,
            step: `/wizard-drafts/${draft.uuid}/step`,
            publish: `/wizard-drafts/${draft.uuid}/publish`,
            abandon: `/wizard-drafts/${draft.uuid}`,
        },
    });
    const [confirmOpen, setConfirmOpen] = useState(false);

    const step = wizard.step;
    const stepNumber = wizard.stepIndex + 1;
    const totalSteps = draft.steps.length;

    const handleNext = async () => {
        if (wizard.isPublishStep) {
            setConfirmOpen(true);

            return;
        }

        await wizard.saveStep();
    };

    if (wizard.expired) {
        return (
            <>
                <Head title="Agent draft expired" />
                <div className="mx-auto w-full max-w-2xl">
                    <div className="rounded-xl border border-border bg-card p-6">
                        <AlertCircle
                            className="size-5 text-amber-600"
                            aria-hidden
                        />
                        <h1 className="mt-3 text-xl font-semibold">
                            This agent draft expired
                        </h1>
                        <p className="mt-2 text-sm leading-6 text-muted-foreground">
                            Start again from the transaction to configure a new
                            agent.
                        </p>
                        <Button asChild className="mt-5">
                            <Link href={backUrl}>Back to transaction</Link>
                        </Button>
                    </div>
                </div>
            </>
        );
    }

    return (
        <>
            <Head title={step?.label ?? 'Configure your agent'} />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <div className="flex items-center justify-between gap-3">
                    <Button asChild variant="ghost" size="sm">
                        <Link href={backUrl}>
                            <ArrowLeft className="size-4" aria-hidden />
                            {transaction.title}
                        </Link>
                    </Button>
                    <p className="text-xs text-muted-foreground">
                        Draft v{wizard.version}
                    </p>
                </div>

                <WizardLayout
                    title={draft.title}
                    description={draft.description}
                    steps={draft.steps}
                    currentStep={wizard.currentStep}
                    completedSteps={wizard.completedSteps}
                    onStepClick={wizard.goToStep}
                >
                    {wizard.conflict && (
                        <div
                            role="alert"
                            className="mb-6 rounded-lg border border-amber-600/30 bg-amber-500/10 p-4 text-sm text-amber-800 dark:text-amber-300"
                        >
                            {wizard.conflict}
                        </div>
                    )}

                    <WizardValidationSummary errors={wizard.errors} />

                    {wizard.message && (
                        <p
                            role="alert"
                            className="mb-6 rounded-lg border border-destructive/30 bg-destructive/5 p-3 text-sm text-destructive"
                        >
                            {wizard.message}
                        </p>
                    )}

                    <WizardStep
                        key={wizard.currentStep}
                        title={step?.label ?? 'Agent step'}
                        description={step?.description ?? ''}
                        stepNumber={stepNumber}
                        totalSteps={totalSteps}
                    >
                        {wizard.currentStep === 'provider' && (
                            <ProviderStep
                                data={wizard.data}
                                errors={wizard.errors}
                                providers={providers}
                                balanceMinor={balanceMinor}
                                updateField={wizard.updateField}
                            />
                        )}
                        {wizard.currentStep === 'objective' && (
                            <ObjectiveStep
                                data={wizard.data}
                                errors={wizard.errors}
                                providers={providers}
                                balanceMinor={balanceMinor}
                                updateField={wizard.updateField}
                            />
                        )}
                        {wizard.currentStep === 'constraints' && (
                            <ConstraintsStep
                                data={wizard.data}
                                errors={wizard.errors}
                                providers={providers}
                                balanceMinor={balanceMinor}
                                updateField={wizard.updateField}
                            />
                        )}
                        {wizard.currentStep === 'limits' && (
                            <LimitsStep
                                data={wizard.data}
                                errors={wizard.errors}
                                providers={providers}
                                balanceMinor={balanceMinor}
                                updateField={wizard.updateField}
                            />
                        )}
                        {wizard.currentStep === 'review' && (
                            <ReviewStep
                                data={wizard.data}
                                errors={wizard.errors}
                                providers={providers}
                                balanceMinor={balanceMinor}
                                updateField={wizard.updateField}
                                onEdit={wizard.goToStep}
                            />
                        )}
                        {wizard.currentStep === 'publish' && (
                            <PublishStep
                                data={wizard.data}
                                errors={wizard.errors}
                                providers={providers}
                                balanceMinor={balanceMinor}
                                updateField={wizard.updateField}
                            />
                        )}
                    </WizardStep>

                    <WizardNavigation
                        onBack={wizard.goBack}
                        onSaveDraft={wizard.saveDraft}
                        onNext={handleNext}
                        onCancel={() => router.visit(backUrl)}
                        backDisabled={wizard.isFirstStep}
                        processing={wizard.processing}
                        saveState={wizard.saveState}
                        nextLabel={
                            wizard.isPublishStep
                                ? 'Activate agent'
                                : wizard.currentStep === 'review'
                                  ? 'Confirm and continue'
                                  : 'Save and continue'
                        }
                    />
                </WizardLayout>
            </div>

            <WizardConfirmDialog
                open={confirmOpen}
                onOpenChange={setConfirmOpen}
                onConfirm={async () => {
                    const published = await wizard.publish();

                    if (!published) {
                        setConfirmOpen(false);
                    }
                }}
                processing={wizard.processing}
                title="Activate this agent?"
                description="The agent can negotiate inside the private limits you set. It cannot approve terms or pay."
                confirmLabel="Activate agent"
            />
        </>
    );
}

AgentWizard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: '/dashboard',
        },
        {
            title: 'Transactions',
            href: '/transactions',
        },
        {
            title: 'Configure agent',
            href: '/transactions',
        },
    ],
};
