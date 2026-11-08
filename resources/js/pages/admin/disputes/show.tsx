import { Head, Link } from '@inertiajs/react';
import { Download, ExternalLink, ShieldAlert } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { DealDispute } from '@/types';

export default function AdminDisputeShow({
    dispute,
}: {
    dispute: DealDispute;
}) {
    const csrfToken =
        typeof document !== 'undefined'
            ? (document
                  .querySelector('meta[name="csrf-token"]')
                  ?.getAttribute('content') ?? '')
            : '';

    return (
        <>
            <Head title={`Admin — Dispute #${dispute.id}`} />
            <div className="mx-auto w-full max-w-4xl p-4">
                <div className="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p className="text-xs font-medium tracking-[0.22em] text-muted-foreground uppercase">
                            Dispute #{dispute.id}
                        </p>
                        <h1 className="mt-2 text-2xl font-semibold tracking-tight">
                            {dispute.order?.transaction?.title ??
                                `Order #${dispute.order_id}`}
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Opened by {dispute.opener?.name ?? 'a party'} ·{' '}
                            {dispute.reason}
                        </p>
                    </div>
                    <Badge variant="outline">
                        {dispute.status.replaceAll('_', ' ')}
                    </Badge>
                </div>

                <div className="mt-6 grid gap-6 lg:grid-cols-[1.3fr_0.7fr]">
                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <ShieldAlert
                                        className="size-4"
                                        aria-hidden
                                    />
                                    Dispute details
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4 text-sm">
                                <p className="whitespace-pre-line text-muted-foreground">
                                    {dispute.description}
                                </p>
                                {dispute.evidence_url && (
                                    <a
                                        href={dispute.evidence_url}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="inline-flex items-center gap-1 font-medium underline underline-offset-4"
                                    >
                                        Open evidence
                                        <ExternalLink
                                            className="size-3.5"
                                            aria-hidden
                                        />
                                    </a>
                                )}
                                {dispute.evidence_path && (
                                    <a
                                        href={`/disputes/${dispute.id}/evidence`}
                                        className="inline-flex items-center gap-1 font-medium underline underline-offset-4"
                                    >
                                        Download{' '}
                                        {dispute.evidence_original_name ??
                                            'evidence'}
                                        <Download
                                            className="size-3.5"
                                            aria-hidden
                                        />
                                    </a>
                                )}
                                {dispute.resolution && (
                                    <div className="rounded-lg border border-border bg-muted/40 p-3">
                                        <p className="text-xs font-medium text-muted-foreground">
                                            Resolution
                                        </p>
                                        <p className="mt-1 whitespace-pre-line">
                                            {dispute.resolution}
                                        </p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Timeline</CardTitle>
                                <CardDescription>
                                    Every dispute action is recorded.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <ol className="space-y-3">
                                    {(dispute.events ?? []).map((event) => (
                                        <li
                                            key={event.id}
                                            className="border-l border-border pl-3 text-sm"
                                        >
                                            <p className="font-medium">
                                                {event.type.replaceAll(
                                                    '_',
                                                    ' ',
                                                )}
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
                                </ol>
                            </CardContent>
                        </Card>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>Record resolution</CardTitle>
                            <CardDescription>
                                Resolutions are visible to both parties.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form
                                method="POST"
                                action={`/admin/disputes/${dispute.id}/resolve`}
                                className="space-y-4"
                            >
                                <input
                                    type="hidden"
                                    name="_token"
                                    value={csrfToken}
                                />
                                <div className="grid gap-2">
                                    <Label htmlFor="status">Status</Label>
                                    <select
                                        id="status"
                                        name="status"
                                        defaultValue={dispute.status}
                                        className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                                    >
                                        <option value="under_review">
                                            under review
                                        </option>
                                        <option value="resolved">
                                            resolved
                                        </option>
                                        <option value="rejected">
                                            rejected
                                        </option>
                                    </select>
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="resolution">
                                        Resolution
                                    </Label>
                                    <Textarea
                                        id="resolution"
                                        name="resolution"
                                        required
                                        rows={5}
                                        defaultValue={dispute.resolution ?? ''}
                                        placeholder="What was decided, and why?"
                                    />
                                </div>
                                <Button type="submit" className="w-full">
                                    Save resolution
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                </div>

                <div className="mt-6">
                    <Button asChild variant="outline">
                        <Link href="/admin/disputes">Back to disputes</Link>
                    </Button>
                </div>
            </div>
        </>
    );
}

AdminDisputeShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Admin', href: '/admin' },
        { title: 'Disputes', href: '/admin/disputes' },
    ],
};
