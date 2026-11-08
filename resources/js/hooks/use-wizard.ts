import { router } from '@inertiajs/react';
import { useCallback, useMemo, useState } from 'react';
import type { WizardDraft, WizardErrors } from '@/types';

type Endpoints = {
    update: string;
    step: string;
    publish: string;
    abandon: string;
};

type SaveState = 'idle' | 'saving' | 'saved' | 'error';

type UseWizardOptions = {
    initialDraft: WizardDraft;
    endpoints: Endpoints;
    servicesUrl: string;
};

function csrfToken(): string {
    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? ''
    );
}

export function useWizard({
    initialDraft,
    endpoints,
    servicesUrl,
}: UseWizardOptions) {
    const [draft, setDraft] = useState(initialDraft);
    const [data, setData] = useState<Record<string, unknown>>(
        initialDraft.data ?? {},
    );
    const [currentStep, setCurrentStep] = useState(initialDraft.current_step);
    const [completedSteps, setCompletedSteps] = useState(
        initialDraft.completed_steps ?? [],
    );
    const [version, setVersion] = useState(initialDraft.version);
    const [errors, setErrors] = useState<WizardErrors>({});
    const [message, setMessage] = useState<string | null>(null);
    const [conflict, setConflict] = useState<string | null>(null);
    const [expired, setExpired] = useState(false);
    const [processing, setProcessing] = useState(false);
    const [saveState, setSaveState] = useState<SaveState>('idle');

    const stepKeys = useMemo(
        () => draft.steps.map((step) => step.key),
        [draft.steps],
    );

    const stepIndex = stepKeys.indexOf(currentStep);
    const step = draft.steps.find((item) => item.key === currentStep);
    const isFirstStep = stepIndex <= 0;
    const isPublishStep = currentStep === 'publish';
    const isReviewStep = step?.review ?? false;
    const progress = Math.min(
        100,
        Math.round(
            (completedSteps.filter((key) => key !== 'publish').length /
                Math.max(
                    1,
                    draft.steps.filter((s) => s.key !== 'publish').length,
                )) *
                100,
        ),
    );

    const applyDraft = useCallback((next: WizardDraft) => {
        setDraft(next);
        setData(next.data ?? {});
        setCurrentStep(next.current_step);
        setCompletedSteps(next.completed_steps ?? []);
        setVersion(next.version);
    }, []);

    const request = useCallback(
        async (url: string, method: string, body: Record<string, unknown>) => {
            const response = await fetch(url, {
                method,
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(body),
            });

            const payload = (await response.json().catch(() => ({}))) as {
                draft?: WizardDraft;
                redirect?: string;
                service?: { url: string };
                message?: string;
                errors?: WizardErrors;
            };

            if (response.status === 409 && payload.draft) {
                applyDraft(payload.draft);
                setConflict(
                    payload.message ??
                        'This draft changed in another tab. The latest version is loaded.',
                );
                setErrors({});
                setSaveState('error');

                return null;
            }

            if (response.status === 410) {
                setExpired(true);
                setMessage(
                    payload.message ??
                        'This draft expired. Start a new listing to continue.',
                );
                setSaveState('error');

                return null;
            }

            if (!response.ok) {
                setErrors(payload.errors ?? {});
                setMessage(
                    payload.message ??
                        'Please fix the highlighted fields before continuing.',
                );
                setSaveState('error');

                return null;
            }

            if (payload.draft) {
                applyDraft(payload.draft);
            }

            setErrors({});
            setMessage(null);
            setConflict(null);
            setSaveState('saved');

            return payload;
        },
        [applyDraft],
    );

    const updateField = useCallback((key: string, value: unknown) => {
        setData((current) => ({ ...current, [key]: value }));
        setSaveState('idle');
    }, []);

    const updateFields = useCallback((values: Record<string, unknown>) => {
        setData((current) => ({ ...current, ...values }));
        setSaveState('idle');
    }, []);

    const saveDraft = useCallback(async () => {
        setProcessing(true);
        setSaveState('saving');

        try {
            await request(endpoints.update, 'PATCH', {
                data,
                current_step: currentStep,
                version,
            });
        } finally {
            setProcessing(false);
        }
    }, [currentStep, data, endpoints.update, request, version]);

    const saveStep = useCallback(async (): Promise<boolean> => {
        setProcessing(true);
        setSaveState('saving');

        try {
            const payload = await request(endpoints.step, 'POST', {
                step: currentStep,
                data,
                version,
            });

            return payload !== null;
        } finally {
            setProcessing(false);
        }
    }, [currentStep, data, endpoints.step, request, version]);

    const publish = useCallback(async (): Promise<boolean> => {
        setProcessing(true);
        setSaveState('saving');

        try {
            const payload = await request(endpoints.publish, 'POST', {
                confirm: true,
                version,
            });

            const destination = payload?.redirect ?? payload?.service?.url;

            if (destination) {
                router.visit(destination);

                return true;
            }

            return false;
        } finally {
            setProcessing(false);
        }
    }, [endpoints.publish, request, version]);

    const goBack = useCallback(() => {
        if (stepIndex <= 0) {
            return;
        }

        setCurrentStep(stepKeys[stepIndex - 1]);
        setErrors({});
        setMessage(null);
    }, [stepIndex, stepKeys]);

    const goToStep = useCallback(
        (key: string) => {
            const index = stepKeys.indexOf(key);

            if (index < 0) {
                return;
            }

            const allowed = completedSteps.includes(key) || key === currentStep;

            if (!allowed) {
                return;
            }

            setCurrentStep(key);
            setErrors({});
            setMessage(null);
        },
        [completedSteps, currentStep, stepIndex, stepKeys],
    );

    const abandon = useCallback(async () => {
        setProcessing(true);

        try {
            await fetch(endpoints.abandon, {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
        } finally {
            setProcessing(false);
            router.visit(servicesUrl);
        }
    }, [endpoints.abandon, servicesUrl]);

    return {
        draft,
        data,
        currentStep,
        completedSteps,
        version,
        errors,
        message,
        conflict,
        expired,
        processing,
        saveState,
        step,
        stepIndex,
        stepKeys,
        isFirstStep,
        isPublishStep,
        isReviewStep,
        progress,
        updateField,
        updateFields,
        saveDraft,
        saveStep,
        publish,
        goBack,
        goToStep,
        abandon,
    };
}
