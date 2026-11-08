import { Head, Link } from '@inertiajs/react';
import { Download, ExternalLink, ShieldAlert } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { DealDispute } from '@/types';

export default function DisputeShow({ dispute }: { dispute: DealDispute }) {
    return (
        <>
            <Head title={`Dispute #${dispute.id}`} />
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

                <Card className="mt-6">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <ShieldAlert className="size-4" aria-hidden />
                            Dispute
                        </CardTitle>
                        <CardDescription>
                            The platform will review the evidence and record a
                            resolution.
                        </CardDescription>
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
                                {dispute.evidence_original_name ?? 'evidence'}
                                <Download className="size-3.5" aria-hidden />
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

                <Card className="mt-6">
                    <CardHeader>
                        <CardTitle>Timeline</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ol className="space-y-3">
                            {(dispute.events ?? []).map((event) => (
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
                        </ol>
                    </CardContent>
                </Card>

                <div className="mt-6 flex gap-2">
                    <Button asChild variant="outline">
                        <Link href={`/orders/${dispute.order_id}`}>
                            Back to order
                        </Link>
                    </Button>
                </div>
            </div>
        </>
    );
}

DisputeShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Orders', href: '/orders' },
        { title: 'Dispute', href: '/orders' },
    ],
};
