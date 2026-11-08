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
import { BudgetStep } from '@/pages/buyer/requests/steps/budget-step';
import { ConditionsStep } from '@/pages/buyer/requests/steps/conditions-step';
import { NegotiationStep } from '@/pages/buyer/requests/steps/negotiation-step';
import { OutcomeStep } from '@/pages/buyer/requests/steps/outcome-step';
import { PublishStep } from '@/pages/buyer/requests/steps/publish-step';
import { ReviewStep } from '@/pages/buyer/requests/steps/review-step';
import { ServiceStep } from '@/pages/buyer/requests/steps/service-step';
import type { MarketplaceService, WizardDraft } from '@/types';

type Props = {
    draft: WizardDraft;
    services: MarketplaceService[];
    backUrl: string;
};

export default function BuyerRequestWizard({
    draft,
    services,
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
                <Head title="Request draft expired" />
                <div className="mx-auto w-full max-w-2xl">
                    <div className="rounded-xl border border-border bg-card p-6">
                        <AlertCircle
                            className="size-5 text-amber-600"
                            aria-hidden
                        />
                        <h1 className="mt-3 text-xl font-semibold">
                            This request draft expired
                        </h1>
                        <p className="mt-2 text-sm leading-6 text-muted-foreground">
                            Drafts are kept for 30 days. Start a new request
                            from a service page.
                        </p>
                        <Button asChild className="mt-5">
                            <Link href={backUrl}>Browse services</Link>
                        </Button>
                    </div>
                </div>
            </>
        );
    }

    return (
        <>
            <Head title={step?.label ?? 'Request a service'} />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <div className="flex items-center justify-between gap-3">
                    <Button asChild variant="ghost" size="sm">
                        <Link href={backUrl}>
                            <ArrowLeft className="size-4" aria-hidden />
                            Services
                        </Link>
                    </Button>
                    <p className="text-xs text-muted-foreground">
                        Draft v{wizard.version}
                    </p>
                </div>

                <WizardLayout
                    title={draft.title}
                    description={draft.description}
                    workspaceLabel="Buyer workspace"
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
                        title={step?.label ?? 'Wizard step'}
                        description={step?.description ?? ''}
                        stepNumber={stepNumber}
                        totalSteps={totalSteps}
                    >
                        {wizard.currentStep === 'service' && (
                            <ServiceStep
                                data={wizard.data}
                                errors={wizard.errors}
                                services={services}
                                updateField={wizard.updateField}
                            />
                        )}
                        {wizard.currentStep === 'outcome' && (
                            <OutcomeStep
                                data={wizard.data}
                                errors={wizard.errors}
                                services={services}
                                updateField={wizard.updateField}
                            />
                        )}
                        {wizard.currentStep === 'budget' && (
                            <BudgetStep
                                data={wizard.data}
                                errors={wizard.errors}
                                services={services}
                                updateField={wizard.updateField}
                            />
                        )}
                        {wizard.currentStep === 'conditions' && (
                            <ConditionsStep
                                data={wizard.data}
                                errors={wizard.errors}
                                services={services}
                                updateField={wizard.updateField}
                            />
                        )}
                        {wizard.currentStep === 'negotiation' && (
                            <NegotiationStep
                                data={wizard.data}
                                errors={wizard.errors}
                                services={services}
                                updateField={wizard.updateField}
                            />
                        )}
                        {wizard.currentStep === 'review' && (
                            <ReviewStep
                                data={wizard.data}
                                errors={wizard.errors}
                                services={services}
                                updateField={wizard.updateField}
                                onEdit={wizard.goToStep}
                            />
                        )}
                        {wizard.currentStep === 'publish' && (
                            <PublishStep
                                data={wizard.data}
                                errors={wizard.errors}
                                services={services}
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
                                ? 'Send request'
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
                title="Send this request?"
                description="The seller will be notified. No payment is taken at this step."
                confirmLabel="Send request"
            />
        </>
    );
}

BuyerRequestWizard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: '/dashboard',
        },
        {
            title: 'Services',
            href: '/services',
        },
        {
            title: 'Request a service',
            href: '/services',
        },
    ],
};
