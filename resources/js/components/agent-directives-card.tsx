import { useState } from 'react';
import { Bot, Send } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';

type Props = {
    transactionId: number;
    hasSession: boolean;
};

function csrfToken(): string {
    if (typeof document === 'undefined') {
        return '';
    }

    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? ''
    );
}

export function AgentDirectivesCard({ transactionId, hasSession }: Props) {
    const [body, setBody] = useState('');
    const [pending, setPending] = useState(false);
    const [sent, setSent] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const send = async () => {
        const text = body.trim();

        if (text === '' || pending || !hasSession) {
            return;
        }

        setPending(true);
        setError(null);
        setSent(false);

        try {
            const response = await fetch(
                `/transactions/${transactionId}/directives`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                    body: JSON.stringify({ body: text }),
                },
            );

            if (!response.ok) {
                const payload = await response.json().catch(() => null);
                throw new Error(
                    payload?.errors?.body?.[0] ??
                        payload?.message ??
                        'Directive not saved.',
                );
            }

            setBody('');
            setSent(true);
        } catch (sendError) {
            setError(
                sendError instanceof Error
                    ? sendError.message
                    : 'Directive not saved.',
            );
        } finally {
            setPending(false);
        }
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <Bot className="size-4" />
                    Message your agent
                </CardTitle>
                <CardDescription>
                    Instructions apply to the next negotiation round only, and
                    never override your budget or floor.
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-3">
                <Textarea
                    value={body}
                    onChange={(event) => setBody(event.target.value)}
                    rows={3}
                    maxLength={1000}
                    placeholder="e.g. Keep delivery under 10 days."
                    aria-label="Directive for your agent"
                    disabled={!hasSession}
                />

                {error !== null && (
                    <p className="text-sm text-destructive" role="alert">
                        {error}
                    </p>
                )}

                {sent && (
                    <p className="text-sm text-muted-foreground">
                        Sent. Your agent will use it in the next round.
                    </p>
                )}

                {!hasSession && (
                    <p className="text-xs text-muted-foreground">
                        Available once the negotiation has started.
                    </p>
                )}

                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => void send()}
                    disabled={!hasSession || pending || body.trim() === ''}
                >
                    <Send className="size-3.5" />
                    {pending ? 'Sending…' : 'Send to agent'}
                </Button>
            </CardContent>
        </Card>
    );
}
