import { Head, Link, router } from '@inertiajs/react';
import { AlertCircle, Check } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { WizardConfirmDialog } from '@/components/wizard/wizard-confirm-dialog';
import { WizardField } from '@/components/wizard/wizard-field';
import { WizardLayout } from '@/components/wizard/wizard-layout';
import { WizardNavigation } from '@/components/wizard/wizard-navigation';
import {
    WizardReview,
    type ReviewSection,
} from '@/components/wizard/wizard-review';
import { WizardStep } from '@/components/wizard/wizard-step';
import { WizardValidationSummary } from '@/components/wizard/wizard-validation-summary';
import { useWizard } from '@/hooks/use-wizard';
import type { WizardDraft } from '@/types';

type Props = {
    draft: WizardDraft;
    dashboardUrl: string;
};

const timezones = [
    'UTC',
    'Africa/Johannesburg',
    'Africa/Lagos',
    'Africa/Nairobi',
    'America/Chicago',
    'America/Los_Angeles',
    'America/New_York',
    'Asia/Dubai',
    'Asia/Kolkata',
    'Asia/Singapore',
    'Australia/Sydney',
    'Europe/Amsterdam',
    'Europe/Berlin',
    'Europe/London',
    'Europe/Paris',
];

const roleOptions = [
    {
        value: 'buyer',
        label: 'Hire someone',
        description: 'Discover services and request work.',
    },
    {
        value: 'seller',
        label: 'Find work',
        description: 'Publish services and deliver work.',
    },
    {
        value: 'both',
        label: 'Both',
        description: 'Buy and sell from one account.',
    },
] as const;

function asString(value: unknown, fallback = ''): string {
    return typeof value === 'string' ? value : fallback;
}

export default function OnboardingWizard({ draft, dashboardUrl }: Props) {
    const wizard = useWizard({
        initialDraft: draft,
        servicesUrl: dashboardUrl,
        endpoints: {
            update: `/wizard-drafts/${draft.uuid}`,
            step: `/wizard-drafts/${draft.uuid}/step`,
            publish: `/wizard-drafts/${draft.uuid}/publish`,
            abandon: `/wizard-drafts/${draft.uuid}`,
        },
    });
    const [confirmOpen, setConfirmOpen] = useState(false);

    const step = wizard.step;
    const currentIndex = wizard.stepIndex;
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
                <Head title="Onboarding expired" />
                <div className="mx-auto w-full max-w-2xl px-4 py-16">
                    <div className="rounded-xl border border-border bg-card p-6">
                        <AlertCircle
                            className="size-5 text-amber-600"
                            aria-hidden
                        />
                        <h1 className="mt-3 text-xl font-semibold">
                            This onboarding draft expired
                        </h1>
                        <p className="mt-2 text-sm leading-6 text-muted-foreground">
                            Start again from the dashboard.
                        </p>
                        <Button asChild className="mt-5">
                            <Link href={dashboardUrl}>Go to dashboard</Link>
                        </Button>
                    </div>
                </div>
            </>
        );
    }

    const reviewSections: ReviewSection[] = [
        {
            key: 'role',
            label: 'Account role',
            items: [
                {
                    label: 'Role',
                    value:
                        roleOptions.find(
                            (option) => option.value === wizard.data.role,
                        )?.label ?? '—',
                },
            ],
            onEdit: () => wizard.goToStep('role'),
        },
        {
            key: 'profile',
            label: 'Profile',
            items: [
                {
                    label: 'Headline',
                    value: asString(wizard.data.headline, '—'),
                },
                { label: 'Bio', value: asString(wizard.data.bio, '—') },
                {
                    label: 'Timezone',
                    value: asString(wizard.data.timezone, 'UTC'),
                },
            ],
            onEdit: () => wizard.goToStep('profile'),
        },
        {
            key: 'preferences',
            label: 'Notifications',
            items: [
                {
                    label: 'Email',
                    value: wizard.data.notification_email
                        ? 'Enabled'
                        : 'Disabled',
                },
                {
                    label: 'In-app',
                    value: wizard.data.notification_in_app
                        ? 'Enabled'
                        : 'Disabled',
                },
            ],
            onEdit: () => wizard.goToStep('preferences'),
        },
    ];

    return (
        <>
            <Head title="Finish your profile" />

            <div className="min-h-screen bg-muted/20">
                <header className="border-b border-border bg-background">
                    <div className="mx-auto flex h-16 w-full max-w-4xl items-center justify-between px-4">
                        <Link
                            href="/"
                            className="flex items-center gap-2 text-sm font-semibold"
                        >
                            <span className="flex size-7 items-center justify-center rounded-md bg-foreground text-background">
                                <Check className="size-3.5" aria-hidden />
                            </span>
                            DealFlow
                        </Link>
                        <Link
                            href={dashboardUrl}
                            className="text-xs text-muted-foreground hover:text-foreground"
                        >
                            Skip for now
                        </Link>
                    </div>
                </header>

                <div className="px-4 py-10">
                    <WizardLayout
                        title={draft.title}
                        description={draft.description}
                        workspaceLabel="Account setup"
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
                            title={step?.label ?? 'Onboarding'}
                            description={step?.description ?? ''}
                            stepNumber={currentIndex + 1}
                            totalSteps={totalSteps}
                        >
                            {wizard.currentStep === 'role' && (
                                <div className="space-y-3">
                                    {roleOptions.map((option) => (
                                        <label
                                            key={option.value}
                                            className={`flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition-colors ${
                                                wizard.data.role ===
                                                option.value
                                                    ? 'border-foreground bg-muted/40'
                                                    : 'border-border hover:bg-muted/30'
                                            }`}
                                        >
                                            <input
                                                type="radio"
                                                name="role"
                                                value={option.value}
                                                checked={
                                                    wizard.data.role ===
                                                    option.value
                                                }
                                                onChange={() =>
                                                    wizard.updateField(
                                                        'role',
                                                        option.value,
                                                    )
                                                }
                                                className="mt-0.5 size-4 accent-emerald-700"
                                            />
                                            <span>
                                                <span className="block text-sm font-medium">
                                                    {option.label}
                                                </span>
                                                <span className="mt-1 block text-sm text-muted-foreground">
                                                    {option.description}
                                                </span>
                                            </span>
                                        </label>
                                    ))}
                                    <InputError
                                        message={
                                            Array.isArray(wizard.errors.role)
                                                ? wizard.errors.role[0]
                                                : wizard.errors.role
                                        }
                                    />
                                </div>
                            )}

                            {wizard.currentStep === 'profile' && (
                                <div className="space-y-5">
                                    <WizardField
                                        label="Headline"
                                        htmlFor="headline"
                                        error={
                                            Array.isArray(
                                                wizard.errors.headline,
                                            )
                                                ? wizard.errors.headline[0]
                                                : wizard.errors.headline
                                        }
                                        hint="A short line buyers and sellers will see."
                                    >
                                        <Input
                                            id="headline"
                                            value={asString(
                                                wizard.data.headline,
                                            )}
                                            onChange={(event) =>
                                                wizard.updateField(
                                                    'headline',
                                                    event.target.value,
                                                )
                                            }
                                            maxLength={120}
                                            placeholder="Laravel developer and product builder"
                                        />
                                    </WizardField>

                                    <WizardField
                                        label="Bio"
                                        htmlFor="bio"
                                        error={
                                            Array.isArray(wizard.errors.bio)
                                                ? wizard.errors.bio[0]
                                                : wizard.errors.bio
                                        }
                                    >
                                        <Textarea
                                            id="bio"
                                            value={asString(wizard.data.bio)}
                                            onChange={(event) =>
                                                wizard.updateField(
                                                    'bio',
                                                    event.target.value,
                                                )
                                            }
                                            rows={5}
                                            maxLength={1000}
                                            placeholder="What you do, what you are good at, and how you work."
                                        />
                                    </WizardField>

                                    <WizardField
                                        label="Timezone"
                                        htmlFor="timezone"
                                        required
                                        error={
                                            Array.isArray(
                                                wizard.errors.timezone,
                                            )
                                                ? wizard.errors.timezone[0]
                                                : wizard.errors.timezone
                                        }
                                    >
                                        <select
                                            id="timezone"
                                            value={asString(
                                                wizard.data.timezone,
                                                '',
                                            )}
                                            onChange={(event) =>
                                                wizard.updateField(
                                                    'timezone',
                                                    event.target.value,
                                                )
                                            }
                                            className="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                        >
                                            <option value="" disabled>
                                                Select a timezone
                                            </option>
                                            {timezones.map((timezone) => (
                                                <option
                                                    key={timezone}
                                                    value={timezone}
                                                >
                                                    {timezone}
                                                </option>
                                            ))}
                                        </select>
                                    </WizardField>
                                </div>
                            )}

                            {wizard.currentStep === 'preferences' && (
                                <div className="space-y-4">
                                    <label className="flex cursor-pointer items-start gap-3 rounded-xl border border-border p-4">
                                        <input
                                            type="checkbox"
                                            checked={Boolean(
                                                wizard.data.notification_email,
                                            )}
                                            onChange={(event) =>
                                                wizard.updateField(
                                                    'notification_email',
                                                    event.target.checked,
                                                )
                                            }
                                            className="mt-0.5 size-4 accent-emerald-700"
                                        />
                                        <span>
                                            <span className="block text-sm font-medium">
                                                Email notifications
                                            </span>
                                            <span className="mt-1 block text-sm text-muted-foreground">
                                                Requests, approvals, and
                                                delivery updates.
                                            </span>
                                        </span>
                                    </label>

                                    <label className="flex cursor-pointer items-start gap-3 rounded-xl border border-border p-4">
                                        <input
                                            type="checkbox"
                                            checked={Boolean(
                                                wizard.data.notification_in_app,
                                            )}
                                            onChange={(event) =>
                                                wizard.updateField(
                                                    'notification_in_app',
                                                    event.target.checked,
                                                )
                                            }
                                            className="mt-0.5 size-4 accent-emerald-700"
                                        />
                                        <span>
                                            <span className="block text-sm font-medium">
                                                In-app notifications
                                            </span>
                                            <span className="mt-1 block text-sm text-muted-foreground">
                                                Keep the notification inbox and
                                                badge up to date.
                                            </span>
                                        </span>
                                    </label>
                                </div>
                            )}

                            {wizard.currentStep === 'review' && (
                                <WizardReview sections={reviewSections} />
                            )}

                            {wizard.currentStep === 'publish' && (
                                <div className="space-y-4 text-sm leading-6 text-muted-foreground">
                                    <p>
                                        Your profile and notification
                                        preferences are ready. Finishing
                                        onboarding does not publish a service or
                                        start a transaction.
                                    </p>
                                    <p>
                                        You can change these settings later from
                                        your profile.
                                    </p>
                                </div>
                            )}
                        </WizardStep>

                        <WizardNavigation
                            onBack={wizard.goBack}
                            onSaveDraft={wizard.saveDraft}
                            onNext={handleNext}
                            onCancel={() => router.visit(dashboardUrl)}
                            backDisabled={wizard.isFirstStep}
                            processing={wizard.processing}
                            saveState={wizard.saveState}
                            nextLabel={
                                wizard.isPublishStep
                                    ? 'Finish onboarding'
                                    : wizard.currentStep === 'review'
                                      ? 'Confirm and continue'
                                      : 'Save and continue'
                            }
                        />
                    </WizardLayout>
                </div>
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
                title="Finish onboarding?"
                description="Your role, profile, and notification preferences will be saved to your account."
                confirmLabel="Finish onboarding"
            />
        </>
    );
}
