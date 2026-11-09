import { useEffect, useRef, useState } from 'react';
import { Paperclip, Send, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import { csrfToken, uploadAttachment } from '@/lib/attachments';

export type ConversationAttachment = {
    id: number;
    name: string;
    mime: string;
    size: number;
    url: string;
};

export type ConversationMessage = {
    id: number;
    body: string;
    user_id: number;
    user_name: string;
    created_at: string | null;
    attachments: ConversationAttachment[];
};

type Props = {
    transactionId: number;
    initialMessages: ConversationMessage[];
    currentUserId: number;
    counterpartName: string;
    unreadCount?: number;
};

function timestamp(value: string | null): string {
    if (value === null) {
        return '';
    }

    return new Date(value).toLocaleString(undefined, {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function fileSize(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${Math.round(bytes / 1024)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

export function ConversationPanel({
    transactionId,
    initialMessages,
    currentUserId,
    counterpartName,
    unreadCount = 0,
}: Props) {
    const [messages, setMessages] =
        useState<ConversationMessage[]>(initialMessages);
    const [body, setBody] = useState('');
    const [pending, setPending] = useState(false);
    const [uploading, setUploading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [attachments, setAttachments] = useState<ConversationAttachment[]>(
        [],
    );
    const fileInput = useRef<HTMLInputElement>(null);
    const listRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const timer = window.setInterval(async () => {
            if (document.hidden) {
                return;
            }

            try {
                const response = await fetch(
                    `/transactions/${transactionId}/messages`,
                    { headers: { Accept: 'application/json' } },
                );

                if (!response.ok) {
                    return;
                }

                const data = (await response.json()) as {
                    messages: ConversationMessage[];
                };

                setMessages(data.messages);
            } catch {
                // Polling is best-effort; the next tick retries.
            }
        }, 15000);

        return () => window.clearInterval(timer);
    }, [transactionId]);

    useEffect(() => {
        listRef.current?.scrollTo({ top: listRef.current.scrollHeight });
    }, [messages.length]);

    const upload = async (file: File) => {
        setUploading(true);
        setError(null);

        try {
            const attachment = await uploadAttachment(file, 'message');

            setAttachments((current) => [...current, attachment]);
        } catch (uploadError) {
            setError(
                uploadError instanceof Error
                    ? uploadError.message
                    : 'Upload failed.',
            );
        } finally {
            setUploading(false);

            if (fileInput.current) {
                fileInput.current.value = '';
            }
        }
    };

    const send = async () => {
        const text = body.trim();

        if (text === '' || pending) {
            return;
        }

        setPending(true);
        setError(null);

        try {
            const response = await fetch(
                `/transactions/${transactionId}/messages`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                    body: JSON.stringify({
                        body: text,
                        attachment_ids: attachments.map((item) => item.id),
                    }),
                },
            );

            if (!response.ok) {
                const payload = await response.json().catch(() => null);
                throw new Error(
                    payload?.errors?.body?.[0] ??
                        payload?.message ??
                        'Message not sent.',
                );
            }

            const data = (await response.json()) as {
                message: ConversationMessage;
            };

            setMessages((current) => [...current, data.message]);
            setBody('');
            setAttachments([]);
        } catch (sendError) {
            setError(
                sendError instanceof Error
                    ? sendError.message
                    : 'Message not sent.',
            );
        } finally {
            setPending(false);
        }
    };

    const onKeyDown = (event: React.KeyboardEvent<HTMLTextAreaElement>) => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            void send();
        }
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <Send className="size-4" />
                    Conversation
                </CardTitle>
                <CardDescription>
                    Direct messages with {counterpartName}. Attach images, PDFs,
                    or ZIP files up to 10 MB.
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                {unreadCount > 0 && messages.length > 0 && (
                    <p className="text-xs font-medium text-muted-foreground">
                        {unreadCount} new since your last visit
                    </p>
                )}
                <div
                    ref={listRef}
                    className="max-h-96 min-h-40 space-y-3 overflow-y-auto pr-1"
                    aria-live="polite"
                >
                    {messages.length === 0 ? (
                        <p className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                            No messages yet. Say hello, clarify scope, or share
                            a file.
                        </p>
                    ) : (
                        messages.map((message) => {
                            const mine = message.user_id === currentUserId;

                            return (
                                <div
                                    key={message.id}
                                    className={
                                        mine
                                            ? 'ml-auto max-w-[85%] rounded-lg border bg-muted/60 p-3'
                                            : 'mr-auto max-w-[85%] rounded-lg border p-3'
                                    }
                                >
                                    <div className="flex flex-wrap items-baseline justify-between gap-2">
                                        <span className="text-xs font-medium">
                                            {mine ? 'You' : message.user_name}
                                        </span>
                                        <span className="text-xs text-muted-foreground tabular-nums">
                                            {timestamp(message.created_at)}
                                        </span>
                                    </div>
                                    <p className="mt-1.5 text-sm leading-6 whitespace-pre-line">
                                        {message.body}
                                    </p>
                                    {message.attachments.length > 0 && (
                                        <ul className="mt-2 space-y-1">
                                            {message.attachments.map(
                                                (attachment) => (
                                                    <li key={attachment.id}>
                                                        <a
                                                            href={
                                                                attachment.url
                                                            }
                                                            className="inline-flex items-center gap-1.5 text-xs font-medium underline underline-offset-2"
                                                            target="_blank"
                                                            rel="noreferrer"
                                                        >
                                                            <Paperclip className="size-3" />
                                                            {attachment.name}
                                                            <span className="text-muted-foreground no-underline">
                                                                (
                                                                {fileSize(
                                                                    attachment.size,
                                                                )}
                                                                )
                                                            </span>
                                                        </a>
                                                    </li>
                                                ),
                                            )}
                                        </ul>
                                    )}
                                </div>
                            );
                        })
                    )}
                </div>

                {attachments.length > 0 && (
                    <ul className="flex flex-wrap gap-2">
                        {attachments.map((attachment) => (
                            <li
                                key={attachment.id}
                                className="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs"
                            >
                                <Paperclip className="size-3" />
                                {attachment.name}
                                <button
                                    type="button"
                                    className="text-muted-foreground hover:text-foreground"
                                    onClick={() =>
                                        setAttachments((current) =>
                                            current.filter(
                                                (item) =>
                                                    item.id !== attachment.id,
                                            ),
                                        )
                                    }
                                    aria-label={`Remove ${attachment.name}`}
                                >
                                    <X className="size-3" />
                                </button>
                            </li>
                        ))}
                    </ul>
                )}

                {error !== null && (
                    <p className="text-sm text-destructive" role="alert">
                        {error}
                    </p>
                )}

                <div className="space-y-2">
                    <Textarea
                        value={body}
                        onChange={(event) => setBody(event.target.value)}
                        onKeyDown={onKeyDown}
                        rows={3}
                        maxLength={3000}
                        placeholder={`Message ${counterpartName}…`}
                        aria-label="Message"
                    />
                    <div className="flex flex-wrap items-center justify-between gap-2">
                        <div className="flex items-center gap-2">
                            <input
                                ref={fileInput}
                                type="file"
                                className="sr-only"
                                accept="image/jpeg,image/png,image/webp,application/pdf,application/zip"
                                onChange={(event) => {
                                    const file = event.target.files?.[0];

                                    if (file) {
                                        void upload(file);
                                    }
                                }}
                            />
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => fileInput.current?.click()}
                                disabled={uploading || pending}
                            >
                                <Paperclip className="size-3.5" />
                                {uploading ? 'Uploading…' : 'Attach'}
                            </Button>
                            <span className="text-xs text-muted-foreground">
                                Enter sends · Shift+Enter adds a line
                            </span>
                        </div>
                        <Button
                            type="button"
                            size="sm"
                            onClick={() => void send()}
                            disabled={
                                pending || uploading || body.trim() === ''
                            }
                        >
                            <Send className="size-3.5" />
                            {pending ? 'Sending…' : 'Send'}
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
