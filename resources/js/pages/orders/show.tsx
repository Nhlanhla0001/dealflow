import { Head, Link, usePage } from '@inertiajs/react';
import { CheckCircle2, ExternalLink, Package } from 'lucide-react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { WizardProgress } from '@/components/wizard/wizard-progress';
import { WizardStep } from '@/components/wizard/wizard-step';
import { formatMoney } from '@/lib/format';
import type { DealOrder } from '@/types';

type Props = {
    order: DealOrder;
    isBuyer: boolean;
    isSeller: boolean;
    revisionLimit: number;
};

const steps = [
    { key: 'payment', label: 'Payment', description: 'The verified payment.' },
    { key: 'start', label: 'Start', description: 'The seller begins work.' },
    {
        key: 'progress',
        label: 'Progress',
        description: 'Updates and delivery.',
    },
    {
        key: 'review',
        label: 'Review',
        description: 'The buyer reviews the delivery.',
    },
    { key: 'complete', label: 'Complete', description: 'The order is closed.' },
];

export default function OrderShow({
    order,
    isBuyer,
    isSeller,
    revisionLimit,
}: Props) {
    const { errors, old } = usePage<{
        errors: Record<string, string>;
        old: Record<string, string>;
    }>().props;

    const currentStep = matchStep(order.status);
    const currentIndex = steps.findIndex((step) => step.key === currentStep);
    const step = steps[currentIndex] ?? steps[0];
    const completedSteps = steps
        .slice(0, Math.max(0, currentIndex))
        .map((item) => item.key);
    const csrfToken =
        typeof document !== 'undefined'
            ? (document
                  .querySelector('meta[name="csrf-token"]')
                  ?.getAttribute('content') ?? '')
            : '';
    const canDispute = [
        'paid',
        'in_progress',
        'delivered',
        'revision_requested',
    ].includes(order.status);

    return (
        <>
            <Head title={`Order #${order.id}`} />

            <div className="mx-auto w-full max-w-5xl">
                <div className="mb-6 flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p className="text-xs font-medium tracking-[0.22em] text-muted-foreground uppercase">
                            Order #{order.id}
                        </p>
                        <h1 className="mt-2 text-2xl font-semibold tracking-tight">
                            {order.transaction?.title ?? 'Order'}
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {order.transaction?.buyer?.name} →{' '}
                            {order.transaction?.seller?.name} ·{' '}
                            {formatMoney(order.amount_minor, order.currency)}
                        </p>
                    </div>
                    <Badge variant={statusVariant(order.status)}>
                        {order.status.replaceAll('_', ' ')}
                    </Badge>
                </div>

                {order.dispute && (
                    <div className="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-destructive/30 bg-destructive/5 p-4">
                        <div>
                            <p className="text-sm font-medium">
                                Dispute #{order.dispute.id}
                            </p>
                            <p className="mt-1 text-xs text-muted-foreground">
                                {order.dispute.reason} ·{' '}
                                {order.dispute.status.replaceAll('_', ' ')}
                            </p>
                        </div>
                        <Button asChild variant="outline" size="sm">
                            <Link href={`/disputes/${order.dispute.id}`}>
                                View dispute
                            </Link>
                        </Button>
                    </div>
                )}

                <WizardProgress
                    steps={steps.map((item) => ({ ...item, review: false }))}
                    currentStep={currentStep}
                    completedSteps={completedSteps}
                />

                <div className="mt-8 grid gap-6 lg:grid-cols-[1.4fr_0.6fr]">
                    <div className="rounded-xl border border-border bg-card p-5 sm:p-6">
                        <WizardStep
                            title={step.label}
                            description={step.description}
                            stepNumber={currentIndex + 1}
                            totalSteps={steps.length}
                        >
                            {order.status === 'paid' && isSeller && (
                                <form
                                    method="POST"
                                    action={`/orders/${order.id}/start`}
                                >
                                    <input
                                        type="hidden"
                                        name="_token"
                                        value={csrfToken}
                                    />
                                    <p className="mb-4 text-sm leading-6 text-muted-foreground">
                                        The payment is verified. Start work when
                                        you are ready; the timeline begins here.
                                    </p>
                                    <Button type="submit">Start work</Button>
                                </form>
                            )}

                            {order.status === 'paid' && isBuyer && (
                                <p className="text-sm text-muted-foreground">
                                    Waiting for the seller to start work.
                                </p>
                            )}

                            {(order.status === 'in_progress' ||
                                order.status === 'revision_requested') &&
                                isSeller && (
                                    <div className="space-y-8">
                                        <form
                                            method="POST"
                                            action={`/orders/${order.id}/progress`}
                                            className="space-y-3"
                                        >
                                            <input
                                                type="hidden"
                                                name="_token"
                                                value={csrfToken}
                                            />
                                            <div className="grid gap-2">
                                                <Label htmlFor="progress">
                                                    Progress update
                                                </Label>
                                                <Textarea
                                                    id="progress"
                                                    name="message"
                                                    required
                                                    rows={3}
                                                    placeholder="What has been completed, and what is next?"
                                                />
                                            </div>
                                            <Button
                                                type="submit"
                                                variant="outline"
                                            >
                                                Add update
                                            </Button>
                                        </form>

                                        <form
                                            method="POST"
                                            action={`/orders/${order.id}/deliver`}
                                            className="space-y-3 border-t border-border pt-6"
                                        >
                                            <input
                                                type="hidden"
                                                name="_token"
                                                value={csrfToken}
                                            />
                                            <div className="grid gap-2">
                                                <Label htmlFor="delivery_message">
                                                    Delivery summary
                                                </Label>
                                                <Textarea
                                                    id="delivery_message"
                                                    name="message"
                                                    required
                                                    rows={4}
                                                    placeholder="Describe the completed deliverables and where to find them."
                                                />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="delivery_url">
                                                    Delivery link (optional)
                                                </Label>
                                                <Input
                                                    id="delivery_url"
                                                    name="delivery_url"
                                                    type="url"
                                                    placeholder="https://example.com/delivery"
                                                />
                                            </div>
                                            <Button type="submit">
                                                Submit delivery
                                            </Button>
                                        </form>
                                    </div>
                                )}

                            {(order.status === 'in_progress' ||
                                order.status === 'revision_requested') &&
                                isBuyer && (
                                    <p className="text-sm text-muted-foreground">
                                        The seller is working. Progress updates
                                        appear in the timeline.
                                    </p>
                                )}

                            {order.status === 'delivered' && isBuyer && (
                                <div className="space-y-8">
                                    <div className="space-y-2 text-sm">
                                        <p className="font-medium">Delivery</p>
                                        <p className="whitespace-pre-line text-muted-foreground">
                                            {order.delivery_message}
                                        </p>
                                        {order.delivery_url && (
                                            <a
                                                href={order.delivery_url}
                                                target="_blank"
                                                rel="noreferrer"
                                                className="inline-flex items-center gap-1 text-sm font-medium underline underline-offset-4"
                                            >
                                                Open delivery
                                                <ExternalLink
                                                    className="size-3.5"
                                                    aria-hidden
                                                />
                                            </a>
                                        )}
                                    </div>

                                    <form
                                        method="POST"
                                        action={`/orders/${order.id}/accept`}
                                    >
                                        <input
                                            type="hidden"
                                            name="_token"
                                            value={csrfToken}
                                        />
                                        <Button type="submit">
                                            <CheckCircle2
                                                className="size-4"
                                                aria-hidden
                                            />
                                            Accept delivery
                                        </Button>
                                    </form>

                                    <form
                                        method="POST"
                                        action={`/orders/${order.id}/revision`}
                                        className="space-y-3 border-t border-border pt-6"
                                    >
                                        <input
                                            type="hidden"
                                            name="_token"
                                            value={csrfToken}
                                        />
                                        <p className="text-xs text-muted-foreground">
                                            {order.revisions_used} of{' '}
                                            {revisionLimit} revision rounds
                                            used.
                                        </p>
                                        <div className="grid gap-2">
                                            <Label htmlFor="revision_message">
                                                Request a revision
                                            </Label>
                                            <Textarea
                                                id="revision_message"
                                                name="message"
                                                required
                                                rows={3}
                                                placeholder="What needs to change?"
                                            />
                                        </div>
                                        <Button type="submit" variant="outline">
                                            Request revision
                                        </Button>
                                    </form>
                                </div>
                            )}

                            {order.status === 'delivered' && isSeller && (
                                <p className="text-sm text-muted-foreground">
                                    Delivery submitted. Waiting for the buyer to
                                    accept or request a revision.
                                </p>
                            )}

                            {order.status === 'completed' && (
                                <p className="flex items-center gap-2 text-sm font-medium text-emerald-700 dark:text-emerald-400">
                                    <CheckCircle2
                                        className="size-4"
                                        aria-hidden
                                    />
                                    Order completed
                                    {order.completed_at
                                        ? ` · ${new Date(order.completed_at).toLocaleString()}`
                                        : ''}
                                </p>
                            )}

                            {order.status === 'disputed' && (
                                <p className="text-sm text-muted-foreground">
                                    This order is in dispute. The event history
                                    is preserved for review.
                                </p>
                            )}

                            {canDispute && (
                                <form
                                    method="POST"
                                    action={`/orders/${order.id}/dispute`}
                                    encType="multipart/form-data"
                                    className="mt-8 space-y-3 border-t border-border pt-6"
                                >
                                    <input
                                        type="hidden"
                                        name="_token"
                                        value={csrfToken}
                                    />
                                    <div className="grid gap-2">
                                        <Label htmlFor="reason">
                                            Open a dispute
                                        </Label>
                                        <Input
                                            id="reason"
                                            name="reason"
                                            required
                                            defaultValue={old.reason ?? ''}
                                            placeholder="Delivery issue"
                                            aria-invalid={Boolean(
                                                errors.reason,
                                            )}
                                        />
                                        <InputError message={errors.reason} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="evidence">
                                            Evidence file (optional)
                                        </Label>
                                        <Input
                                            id="evidence"
                                            name="evidence"
                                            type="file"
                                            accept=".pdf,.png,.jpg,.jpeg,.webp,.zip"
                                            aria-invalid={Boolean(
                                                errors.evidence,
                                            )}
                                            className="file:mr-3 file:rounded-md file:border-0 file:bg-muted file:px-3 file:py-1.5 file:text-xs file:font-medium"
                                        />
                                        <p className="text-xs text-muted-foreground">
                                            PDF, PNG, JPG, WebP, or ZIP up to 10
                                            MB. Stored privately and shared only
                                            with the other party and admins.
                                        </p>
                                        <InputError message={errors.evidence} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="evidence_url">
                                            Or an evidence link (optional)
                                        </Label>
                                        <Input
                                            id="evidence_url"
                                            name="evidence_url"
                                            type="url"
                                            defaultValue={
                                                old.evidence_url ?? ''
                                            }
                                            placeholder="https://example.com/evidence"
                                            aria-invalid={Boolean(
                                                errors.evidence_url,
                                            )}
                                        />
                                        <InputError
                                            message={errors.evidence_url}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="dispute_message">
                                            Describe the issue
                                        </Label>
                                        <Textarea
                                            id="dispute_message"
                                            name="message"
                                            required
                                            rows={3}
                                            defaultValue={old.message ?? ''}
                                            aria-invalid={Boolean(
                                                errors.message,
                                            )}
                                            placeholder="What went wrong, and what outcome are you asking for?"
                                        />
                                        <InputError message={errors.message} />
                                    </div>
                                    <Button type="submit" variant="destructive">
                                        Submit dispute
                                    </Button>
                                </form>
                            )}
                        </WizardStep>
                    </div>

                    <div className="space-y-6">
                        <div className="rounded-xl border border-border bg-card p-5">
                            <h2 className="flex items-center gap-2 text-sm font-semibold">
                                <Package className="size-4" aria-hidden />
                                Order summary
                            </h2>
                            <dl className="mt-4 space-y-3 text-sm">
                                <div className="flex justify-between gap-3">
                                    <dt className="text-muted-foreground">
                                        Amount
                                    </dt>
                                    <dd className="font-medium">
                                        {formatMoney(
                                            order.amount_minor,
                                            order.currency,
                                        )}
                                    </dd>
                                </div>
                                <div className="flex justify-between gap-3">
                                    <dt className="text-muted-foreground">
                                        Revisions
                                    </dt>
                                    <dd className="font-medium">
                                        {order.revisions_used} / {revisionLimit}
                                    </dd>
                                </div>
                                <div className="flex justify-between gap-3">
                                    <dt className="text-muted-foreground">
                                        Paid
                                    </dt>
                                    <dd className="text-right font-medium">
                                        {order.paid_at
                                            ? new Date(
                                                  order.paid_at,
                                              ).toLocaleDateString()
                                            : '—'}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <div className="rounded-xl border border-border bg-card p-5">
                            <h2 className="text-sm font-semibold">Timeline</h2>
                            <ol className="mt-4 space-y-3">
                                {(order.events ?? []).map((event) => (
                                    <li
                                        key={event.id}
                                        className="border-l border-border pl-3 text-sm"
                                    >
                                        <p className="font-medium">
                                            {event.type.replaceAll('_', ' ')}
                                        </p>
                                        {event.message && (
                                            <p className="mt-1 whitespace-pre-line text-muted-foreground">
                                                {event.message}
                                            </p>
                                        )}
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            {event.user?.name ?? 'System'} ·{' '}
                                            {new Date(
                                                event.created_at,
                                            ).toLocaleString()}
                                        </p>
                                    </li>
                                ))}
                                {(order.events ?? []).length === 0 && (
                                    <li className="text-sm text-muted-foreground">
                                        No events yet.
                                    </li>
                                )}
                            </ol>
                        </div>
                    </div>
                </div>

                <div className="mt-6">
                    <Button asChild variant="outline">
                        <Link href={`/transactions/${order.transaction_id}`}>
                            Back to transaction
                        </Link>
                    </Button>
                </div>
            </div>
        </>
    );
}

function matchStep(status: string): string {
    return (
        {
            paid: 'start',
            in_progress: 'progress',
            revision_requested: 'progress',
            delivered: 'review',
            completed: 'complete',
            disputed: 'review',
        }[status] ?? 'payment'
    );
}

function statusVariant(
    status: string,
): 'default' | 'secondary' | 'outline' | 'destructive' {
    return (
        ({
            paid: 'default',
            in_progress: 'secondary',
            revision_requested: 'outline',
            delivered: 'secondary',
            completed: 'default',
            disputed: 'destructive',
            cancelled: 'outline',
        }[status] as 'default' | 'secondary' | 'outline' | 'destructive') ??
        'outline'
    );
}

OrderShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Orders', href: '/orders' },
    ],
};
