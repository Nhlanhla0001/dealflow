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
import { BasicsStep } from '@/pages/seller/services/steps/basics-step';
import { CategoryStep } from '@/pages/seller/services/steps/category-step';
import { DeliveryStep } from '@/pages/seller/services/steps/delivery-step';
import { MediaStep } from '@/pages/seller/services/steps/media-step';
import { NegotiationStep } from '@/pages/seller/services/steps/negotiation-step';
import { PreviewStep } from '@/pages/seller/services/steps/preview-step';
import { PricingStep } from '@/pages/seller/services/steps/pricing-step';
import { PublishStep } from '@/pages/seller/services/steps/publish-step';
import type { MarketplaceCategory, WizardDraft } from '@/types';

type Props = {
    draft: WizardDraft;
    categories: MarketplaceCategory[];
    expired: boolean;
    servicesUrl: string;
};

export default function SellerServiceWizard({
    draft,
    categories,
    expired,
    servicesUrl,
}: Props) {
    const wizard = useWizard({
        initialDraft: draft,
        servicesUrl,
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

    if (expired || wizard.expired) {
        return (
            <>
                <Head title="Draft expired" />
                <div className="mx-auto w-full max-w-2xl">
                    <div className="rounded-xl border border-border bg-card p-6">
                        <AlertCircle
                            className="size-5 text-amber-600"
                            aria-hidden
                        />
                        <h1 className="mt-3 text-xl font-semibold">
                            This draft expired
                        </h1>
                        <p className="mt-2 text-sm leading-6 text-muted-foreground">
                            Drafts are kept for 30 days. Start a new listing to
                            continue; your previous published services are
                            unaffected.
                        </p>
                        <Button asChild className="mt-5">
                            <Link href="/seller/services/new">
                                Start a new listing
                            </Link>
                        </Button>
                    </div>
                </div>
            </>
        );
    }

    return (
        <>
            <Head title={step?.label ?? 'Publish a service'} />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <div className="flex items-center justify-between gap-3">
                    <Button asChild variant="ghost" size="sm">
                        <Link href={servicesUrl}>
                            <ArrowLeft className="size-4" aria-hidden />
                            My services
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
                        title={step?.label ?? 'Wizard step'}
                        description={step?.description ?? ''}
                        stepNumber={stepNumber}
                        totalSteps={totalSteps}
                    >
                        {wizard.currentStep === 'category' && (
                            <CategoryStep
                                data={wizard.data}
                                errors={wizard.errors}
                                categories={categories}
                                updateField={wizard.updateField}
                                updateFields={wizard.updateFields}
                            />
                        )}
                        {wizard.currentStep === 'basics' && (
                            <BasicsStep
                                data={wizard.data}
                                errors={wizard.errors}
                                categories={categories}
                                updateField={wizard.updateField}
                                updateFields={wizard.updateFields}
                            />
                        )}
                        {wizard.currentStep === 'pricing' && (
                            <PricingStep
                                data={wizard.data}
                                errors={wizard.errors}
                                categories={categories}
                                updateField={wizard.updateField}
                                updateFields={wizard.updateFields}
                            />
                        )}
                        {wizard.currentStep === 'delivery' && (
                            <DeliveryStep
                                data={wizard.data}
                                errors={wizard.errors}
                                categories={categories}
                                updateField={wizard.updateField}
                                updateFields={wizard.updateFields}
                            />
                        )}
                        {wizard.currentStep === 'media' && (
                            <MediaStep
                                data={wizard.data}
                                errors={wizard.errors}
                                categories={categories}
                                updateField={wizard.updateField}
                                updateFields={wizard.updateFields}
                            />
                        )}
                        {wizard.currentStep === 'negotiation' && (
                            <NegotiationStep
                                data={wizard.data}
                                errors={wizard.errors}
                                categories={categories}
                                updateField={wizard.updateField}
                                updateFields={wizard.updateFields}
                            />
                        )}
                        {wizard.currentStep === 'preview' && (
                            <PreviewStep
                                data={wizard.data}
                                errors={wizard.errors}
                                categories={categories}
                                updateField={wizard.updateField}
                                updateFields={wizard.updateFields}
                                onEdit={wizard.goToStep}
                            />
                        )}
                        {wizard.currentStep === 'publish' && (
                            <PublishStep
                                data={wizard.data}
                                errors={wizard.errors}
                                categories={categories}
                                updateField={wizard.updateField}
                                updateFields={wizard.updateFields}
                            />
                        )}
                    </WizardStep>

                    <WizardNavigation
                        onBack={wizard.goBack}
                        onSaveDraft={wizard.saveDraft}
                        onNext={handleNext}
                        onCancel={() => router.visit(servicesUrl)}
                        backDisabled={wizard.isFirstStep}
                        processing={wizard.processing}
                        saveState={wizard.saveState}
                        nextLabel={
                            wizard.isPublishStep
                                ? 'Publish listing'
                                : wizard.currentStep === 'preview'
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
                title="Publish this listing?"
                description="Buyers will be able to discover this service, view the terms, and send a request."
                confirmLabel="Publish listing"
            />
        </>
    );
}

SellerServiceWizard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: '/dashboard',
        },
        {
            title: 'My services',
            href: '/seller/services',
        },
        {
            title: 'Publish a service',
            href: '/seller/services/new',
        },
    ],
};
