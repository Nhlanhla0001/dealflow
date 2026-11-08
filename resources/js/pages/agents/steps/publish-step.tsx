import { Bot, ShieldCheck, Wallet } from 'lucide-react';
import { formatMoney } from '@/lib/format';
import { asString, type AgentStepProps } from './types';

export function PublishStep({ data, providers, balanceMinor }: AgentStepProps) {
    const provider = providers[asString(data.provider)];
    const isSeller = data.role === 'seller';

    return (
        <div className="space-y-6">
            <div className="rounded-xl border border-border p-5">
                <p className="text-xs text-muted-foreground">
                    {isSeller ? 'Seller agent' : 'Buyer agent'}
                </p>
                <h3 className="mt-2 text-xl font-semibold tracking-tight">
                    {provider?.label ?? 'AI provider'} · {asString(data.model)}
                </h3>
                <div className="mt-3 flex flex-wrap gap-4 text-sm">
                    <span className="text-muted-foreground">
                        {asString(data.max_turns)} turns max
                    </span>
                    <span className="text-muted-foreground">
                        {formatMoney(
                            Math.round(Number(data.spending_limit ?? 0) * 100),
                        )}{' '}
                        AI budget
                    </span>
                    <span className="text-muted-foreground">
                        {formatMoney(balanceMinor)} available
                    </span>
                </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-3">
                <div className="rounded-xl border border-border p-4">
                    <Bot className="size-4 text-muted-foreground" aria-hidden />
                    <p className="mt-3 text-sm font-medium">
                        Independent configuration
                    </p>
                    <p className="mt-1 text-sm leading-6 text-muted-foreground">
                        The other participant configures their own agent
                        separately.
                    </p>
                </div>
                <div className="rounded-xl border border-border p-4">
                    <ShieldCheck
                        className="size-4 text-emerald-700 dark:text-emerald-400"
                        aria-hidden
                    />
                    <p className="mt-3 text-sm font-medium">
                        Private limits stay private
                    </p>
                    <p className="mt-1 text-sm leading-6 text-muted-foreground">
                        Your floor or maximum budget is never sent to the other
                        agent.
                    </p>
                </div>
                <div className="rounded-xl border border-border p-4">
                    <Wallet
                        className="size-4 text-muted-foreground"
                        aria-hidden
                    />
                    <p className="mt-3 text-sm font-medium">Budget is gated</p>
                    <p className="mt-1 text-sm leading-6 text-muted-foreground">
                        The spending limit is checked against the credit ledger
                        before activation. Per-call metering is the next step.
                    </p>
                </div>
            </div>

            <p className="text-sm leading-6 text-muted-foreground">
                Activating the agent does not approve terms or start a payment.
                It only allows bounded negotiation on your behalf.
            </p>
        </div>
    );
}
