import { Head } from '@inertiajs/react';
import { Bell, CheckCircle2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';
import { index as notificationsIndex } from '@/routes/notifications';
import type { AppNotification } from '@/types';

export default function NotificationsIndex({
    notifications,
}: {
    notifications: AppNotification[];
}) {
    return (
        <>
            <Head title="Notifications" />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <div>
                    <p className="text-xs font-medium tracking-[0.22em] text-muted-foreground uppercase">
                        DealFlow
                    </p>
                    <h1 className="mt-2 text-2xl font-semibold tracking-tight">
                        Notifications
                    </h1>
                    <p className="mt-1 max-w-2xl text-sm text-muted-foreground">
                        Approval requests, negotiation updates, and payment
                        events.
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Inbox</CardTitle>
                        <CardDescription>
                            {notifications.length} notification
                            {notifications.length === 1 ? '' : 's'}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {notifications.length === 0 ? (
                            <div className="rounded-lg border border-dashed p-8 text-center">
                                <CheckCircle2 className="mx-auto size-6 text-muted-foreground" />
                                <p className="mt-2 font-medium">
                                    You’re all caught up
                                </p>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Approval requests and deal updates will
                                    appear here.
                                </p>
                            </div>
                        ) : (
                            <ul className="divide-y">
                                {notifications.map((notification) => (
                                    <li
                                        key={notification.id}
                                        className="flex gap-3 py-4 first:pt-0 last:pb-0"
                                    >
                                        <span className="mt-0.5 rounded-lg bg-muted p-2 text-muted-foreground">
                                            <Bell className="size-4" />
                                        </span>
                                        <div className="min-w-0 flex-1">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <p className="font-medium">
                                                    {notification.data.title}
                                                </p>
                                                {!notification.read_at && (
                                                    <Badge variant="secondary">
                                                        New
                                                    </Badge>
                                                )}
                                            </div>
                                            <p className="mt-1 text-sm text-muted-foreground">
                                                {notification.data.message}
                                            </p>
                                            <a
                                                href={notification.data.url}
                                                className="mt-2 inline-flex text-sm font-medium text-primary hover:underline"
                                            >
                                                Open transaction
                                            </a>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

NotificationsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
        {
            title: 'Notifications',
            href: notificationsIndex(),
        },
    ],
};
