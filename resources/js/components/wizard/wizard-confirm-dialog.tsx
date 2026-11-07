import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onConfirm: () => void;
    processing: boolean;
    title: string;
    description: string;
    confirmLabel: string;
};

export function WizardConfirmDialog({
    open,
    onOpenChange,
    onConfirm,
    processing,
    title,
    description,
    confirmLabel,
}: Props) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription>{description}</DialogDescription>
                </DialogHeader>
                <p className="rounded-lg border border-border bg-muted/40 p-3 text-sm text-muted-foreground">
                    Publishing makes this listing public and discoverable. You
                    can unpublish or archive it later.
                </p>
                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => onOpenChange(false)}
                        disabled={processing}
                    >
                        Keep editing
                    </Button>
                    <Button
                        type="button"
                        onClick={onConfirm}
                        disabled={processing}
                    >
                        {processing && <Spinner />}
                        {confirmLabel}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
