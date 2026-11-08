import { useState } from 'react';
import { ArrowRight, Check, LockKeyhole, ShieldCheck } from 'lucide-react';

type View = 'buyer' | 'seller';

type Message = {
    agent: 'Buyer AI' | 'Seller AI' | 'Seller';
    price: string;
    reason: string;
    tone: 'buyer' | 'seller';
};

const messages: Message[] = [
    {
        agent: 'Buyer AI',
        price: '$400',
        reason: 'Opening offer inside the $500 budget.',
        tone: 'buyer',
    },
    {
        agent: 'Seller AI',
        price: '$580',
        reason: 'Counter above the buyer’s $500 budget. Each agent obeys only its own side’s limits.',
        tone: 'seller',
    },
    {
        agent: 'Buyer AI',
        price: '$500',
        reason: 'Final offer at the budget cap.',
        tone: 'buyer',
    },
    {
        agent: 'Seller AI',
        price: '$500',
        reason: 'Agent accepts at the budget cap. Human approval is still pending.',
        tone: 'seller',
    },
];

export function NegotiationPreview() {
    const [view, setView] = useState<View>('buyer');
    const [approved, setApproved] = useState(false);

    return (
        <div>
            <div className="overflow-hidden rounded-2xl border border-border bg-card shadow-[0_24px_70px_-32px_rgba(0,0,0,0.45)]">
                <div className="flex items-start justify-between gap-4 border-b border-border px-5 py-4">
                    <div>
                        <p className="font-mono text-xs tracking-[0.16em] text-muted-foreground uppercase">
                            Deal #4
                        </p>
                        <p className="mt-1 text-sm font-semibold">
                            Product launch
                        </p>
                    </div>
                    <span
                        className={`inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium ${
                            approved
                                ? 'border-emerald-600/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400'
                                : 'border-amber-600/30 bg-amber-500/10 text-amber-700 dark:text-amber-400'
                        }`}
                    >
                        <span
                            className={`size-1.5 rounded-full ${
                                approved ? 'bg-emerald-600' : 'bg-amber-600'
                            }`}
                        />
                        {approved ? 'Approved by both' : 'Awaiting approval'}
                    </span>
                </div>

                <div className="flex items-center gap-1 border-b border-border px-5 py-2.5">
                    <span className="mr-2 text-xs font-medium text-muted-foreground">
                        View as
                    </span>
                    {(['buyer', 'seller'] as View[]).map((option) => (
                        <button
                            key={option}
                            type="button"
                            onClick={() => setView(option)}
                            aria-pressed={view === option}
                            className={`rounded-md px-2.5 py-1 text-xs font-medium capitalize transition-colors ${
                                view === option
                                    ? 'bg-foreground text-background'
                                    : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                            }`}
                        >
                            {option}
                        </button>
                    ))}
                </div>

                <div className="grid grid-cols-2 gap-px border-b border-border bg-border">
                    <div className="bg-card px-5 py-4">
                        <p className="text-xs text-muted-foreground">
                            Buyer budget
                        </p>
                        <p className="mt-1 font-mono text-lg font-semibold tabular-nums">
                            $500
                        </p>
                    </div>
                    <div className="bg-card px-5 py-4">
                        <p className="flex items-center gap-1.5 text-xs text-muted-foreground">
                            Seller floor
                            {view === 'buyer' && (
                                <LockKeyhole className="size-3" aria-hidden />
                            )}
                        </p>
                        <p
                            className={`mt-1 font-mono text-lg font-semibold tabular-nums ${
                                view === 'buyer'
                                    ? 'text-muted-foreground'
                                    : 'text-emerald-700 dark:text-emerald-400'
                            }`}
                        >
                            {view === 'buyer' ? 'Private' : '$450'}
                        </p>
                    </div>
                </div>

                <ol className="px-5 py-5">
                    {messages.map((message, index) => (
                        <li
                            key={`${message.agent}-${index}`}
                            className="relative grid grid-cols-[1.75rem_1fr] gap-3"
                        >
                            <div className="flex flex-col items-center">
                                <span
                                    className={`flex size-7 shrink-0 items-center justify-center rounded-full border text-[11px] font-semibold ${
                                        message.tone === 'buyer'
                                            ? 'border-border bg-background text-foreground'
                                            : 'border-emerald-600/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400'
                                    }`}
                                >
                                    {message.tone === 'buyer' ? 'B' : 'S'}
                                </span>
                                {index < messages.length - 1 && (
                                    <span className="mt-1 w-px flex-1 bg-border" />
                                )}
                            </div>
                            <div className="pb-5">
                                <div className="flex items-baseline justify-between gap-3">
                                    <span className="text-xs font-medium text-muted-foreground">
                                        {message.agent}
                                    </span>
                                    <span className="font-mono text-sm font-semibold tabular-nums">
                                        {message.price}
                                    </span>
                                </div>
                                <p className="mt-1 text-sm leading-6">
                                    {index === 1 && view === 'buyer'
                                        ? 'Counter to protect the seller’s margin.'
                                        : message.reason}
                                </p>
                            </div>
                        </li>
                    ))}
                </ol>

                <div className="border-t border-border px-5 py-4">
                    <p className="mb-3 text-xs leading-5 text-muted-foreground">
                        Agent acceptance is a proposal. The approvals below are
                        separate human actions.
                    </p>
                    <div className="flex items-center justify-between text-sm">
                        <span className="text-muted-foreground">
                            Buyer approval
                        </span>
                        <span className="inline-flex items-center gap-1.5 font-medium text-emerald-700 dark:text-emerald-400">
                            <Check className="size-3.5" aria-hidden />
                            Approved
                        </span>
                    </div>
                    <div className="mt-2 flex items-center justify-between text-sm">
                        <span className="text-muted-foreground">
                            Seller approval
                        </span>
                        <span
                            className={`inline-flex items-center gap-1.5 font-medium ${
                                approved
                                    ? 'text-emerald-700 dark:text-emerald-400'
                                    : 'text-amber-700 dark:text-amber-400'
                            }`}
                        >
                            {approved ? (
                                <Check className="size-3.5" aria-hidden />
                            ) : (
                                <span className="size-1.5 rounded-full bg-amber-600" />
                            )}
                            {approved ? 'Approved' : 'Pending'}
                        </span>
                    </div>

                    <button
                        type="button"
                        onClick={() => setApproved((value) => !value)}
                        className={`mt-4 flex w-full items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium transition-colors ${
                            approved
                                ? 'bg-emerald-700 text-white hover:bg-emerald-800'
                                : 'bg-foreground text-background hover:opacity-90'
                        }`}
                    >
                        {approved ? (
                            <>
                                <ShieldCheck className="size-4" aria-hidden />
                                PayPal checkout unlocked
                            </>
                        ) : (
                            <>
                                Preview seller approval
                                <ArrowRight className="size-4" aria-hidden />
                            </>
                        )}
                    </button>
                    <p className="mt-2 text-center text-xs text-muted-foreground">
                        {approved
                            ? 'Both humans approved. Payment can now be created.'
                            : 'Interactive preview. No real approval is recorded.'}
                    </p>
                </div>
            </div>

            <p className="mt-3 flex items-start gap-2 text-xs leading-5 text-muted-foreground">
                <LockKeyhole className="mt-0.5 size-3.5 shrink-0" aria-hidden />
                The seller floor never enters the buyer agent's context, and
                each agent answers to its own party's limits. The buyer's
                validator rejects any price above the budget. Switch views to
                compare.
            </p>
        </div>
    );
}
