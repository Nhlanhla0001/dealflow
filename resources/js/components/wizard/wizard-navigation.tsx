import { ArrowLeft, ArrowRight, Save, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { WizardSavedState } from '@/components/wizard/wizard-validation-summary';

type Props = {
    onBack: () => void;
    onSaveDraft: () => void;
    onNext: () => void;
    onCancel: () => void;
    backDisabled?: boolean;
    processing: boolean;
    saveState: 'idle' | 'saving' | 'saved' | 'error';
    nextLabel: string;
    nextDisabled?: boolean;
};

export function WizardNavigation({
    onBack,
    onSaveDraft,
    onNext,
    onCancel,
    backDisabled = false,
    processing,
    saveState,
    nextLabel,
    nextDisabled = false,
}: Props) {
    return (
        <div className="mt-8 flex flex-col gap-4 border-t border-border pt-5">
            <div className="flex flex-wrap items-center gap-2">
                <Button
                    type="button"
                    variant="outline"
                    onClick={onBack}
                    disabled={backDisabled || processing}
                >
                    <ArrowLeft className="size-4" aria-hidden />
                    Back
                </Button>

                <Button
                    type="button"
                    variant="ghost"
                    onClick={onSaveDraft}
                    disabled={processing}
                >
                    <Save className="size-4" aria-hidden />
                    Save draft
                </Button>

                <WizardSavedState state={saveState} />

                <div className="ml-auto flex items-center gap-2">
                    <Button
                        type="button"
                        variant="ghost"
                        onClick={onCancel}
                        disabled={processing}
                    >
                        <X className="size-4" aria-hidden />
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        onClick={onNext}
                        disabled={processing || nextDisabled}
                    >
                        {processing && <Spinner />}
                        {nextLabel}
                        <ArrowRight className="size-4" aria-hidden />
                    </Button>
                </div>
            </div>
            <p className="text-xs text-muted-foreground">
                Your progress is saved to this draft. You can leave and resume
                it later.
            </p>
        </div>
    );
}
