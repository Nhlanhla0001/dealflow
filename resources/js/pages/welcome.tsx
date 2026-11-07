import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    Check,
    FileCheck2,
    LockKeyhole,
    ShieldCheck,
    Wallet,
} from 'lucide-react';
import { NegotiationPreview } from '@/components/marketing/negotiation-preview';
import { ServiceCard } from '@/components/marketplace/service-card';
import { dashboard, login, register } from '@/routes';
import { show as transactionsShow } from '@/routes/transactions';
import type { MarketplaceCategory, MarketplaceService } from '@/types';

const limits = [
    {
        name: 'Buyer budget',
        owner: 'Deterministic validator',
        detail: 'The buyer agent gets a ceiling, never the seller floor.',
    },
    {
        name: 'Seller floor',
        owner: 'Deterministic validator',
        detail: 'Visible only in the seller view. The buyer sees “Private”.',
    },
    {
        name: 'Final approval',
        owner: 'Both humans',
        detail: 'No agent tool can approve terms on a person’s behalf.',
    },
    {
        name: 'Money movement',
        owner: 'PayPal Orders API',
        detail: 'Checkout unlocks only after both approvals, and capture is verified.',
    },
];

const steps = [
    {
        title: 'Buyer sets the ceiling',
        detail: 'A budget, a delivery window, and the scope.',
    },
    {
        title: 'Seller sets the floor',
        detail: 'An opening offer and a private minimum.',
    },
    {
        title: 'Agents negotiate',
        detail: 'Real proposals, real counters, inside the limits.',
    },
    {
        title: 'Both humans approve',
        detail: 'One rejection cancels the deal. No silent consent.',
    },
    {
        title: 'PayPal settles',
        detail: 'Order, capture, signed webhook, audit trail.',
    },
];

const audit = [
    ['transaction.created', '$500 budget · 7 days'],
    ['offer.submitted', '$650 opening · $450 floor'],
    ['agreement.proposed', '$500 · 7 days'],
    ['agreement.approved', 'buyer + seller'],
    ['payment.captured', '84437967V6387225V'],
];

const tools = [
    [
        'Gemini 3 Flash',
        'Buyer and seller proposals through the Laravel AI SDK.',
    ],
    ['PayPal Orders API v2', 'Sandbox order creation and verified capture.'],
    [
        'PayPal webhooks',
        'Signature verification and idempotent event handling.',
    ],
    [
        'Laravel 13 + Inertia 3',
        'Server-driven pages with React 19 on the client.',
    ],
    ['SQLite', 'Demo data, approvals, payment events, and audit log.'],
    ['Automated tests', 'Negotiation, approval, rejection, and payment paths.'],
];

type Props = {
    categories?: MarketplaceCategory[];
    featuredServices?: MarketplaceService[];
};

export default function Welcome({
    categories = [],
    featuredServices = [],
}: Props) {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="AI negotiates. Humans approve. PayPal settles." />

            <div className="min-h-screen bg-background text-foreground">
                <header className="border-b border-border">
                    <div className="mx-auto flex h-16 w-full max-w-6xl items-center justify-between gap-4 px-6">
                        <Link
                            href="/"
                            className="flex items-center gap-2.5 text-sm font-semibold"
                        >
                            <span className="flex size-8 items-center justify-center rounded-lg bg-foreground text-background">
                                <Check className="size-4" aria-hidden />
                            </span>
                            <span>
                                DealFlow <span className="font-normal">AI</span>
                            </span>
                        </Link>

                        <nav className="hidden items-center gap-7 text-sm text-muted-foreground md:flex">
                            <Link
                                href="/services"
                                className="transition-colors hover:text-foreground"
                            >
                                Marketplace
                            </Link>
                            <a
                                href="#trust"
                                className="transition-colors hover:text-foreground"
                            >
                                Trust model
                            </a>
                            <a
                                href="#how"
                                className="transition-colors hover:text-foreground"
                            >
                                How it works
                            </a>
                            <a
                                href="#tools"
                                className="transition-colors hover:text-foreground"
                            >
                                Tools
                            </a>
                        </nav>

                        <div className="flex items-center gap-2">
                            {auth.user ? (
                                <Link
                                    href={dashboard()}
                                    className="inline-flex items-center gap-2 rounded-lg bg-foreground px-4 py-2 text-sm font-medium text-background transition-opacity hover:opacity-90"
                                >
                                    Open dashboard
                                    <ArrowRight
                                        className="size-4"
                                        aria-hidden
                                    />
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={login()}
                                        className="hidden rounded-lg px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground sm:inline-flex"
                                    >
                                        Log in
                                    </Link>
                                    <Link
                                        href={login()}
                                        className="inline-flex items-center gap-2 rounded-lg bg-foreground px-4 py-2 text-sm font-medium text-background transition-opacity hover:opacity-90"
                                    >
                                        Open demo
                                        <ArrowRight
                                            className="size-4"
                                            aria-hidden
                                        />
                                    </Link>
                                </>
                            )}
                        </div>
                    </div>
                </header>

                <main>
                    <section className="mx-auto grid w-full max-w-6xl gap-14 px-6 py-12 lg:grid-cols-[1.02fr_0.98fr] lg:items-start lg:gap-16 lg:py-20">
                        <div>
                            <h1 className="max-w-2xl text-4xl leading-[1.04] font-semibold tracking-[-0.035em] text-balance sm:text-5xl lg:text-[3.4rem]">
                                AI negotiates the deal.{' '}
                                <span className="text-emerald-700 dark:text-emerald-400">
                                    You keep the final say.
                                </span>
                            </h1>

                            <p className="mt-6 max-w-xl text-base leading-7 text-muted-foreground">
                                DealFlow AI runs a real buyer-versus-seller
                                agent negotiation inside the limits each side
                                sets. Nothing is approved, and no money moves,
                                until both humans say yes. PayPal settles the
                                payment.
                            </p>

                            <div className="mt-8 flex flex-wrap items-center gap-3">
                                <Link
                                    href={login()}
                                    className="inline-flex items-center gap-2 rounded-lg bg-foreground px-5 py-3 text-sm font-medium text-background transition-opacity hover:opacity-90"
                                >
                                    Open the live demo
                                    <ArrowRight
                                        className="size-4"
                                        aria-hidden
                                    />
                                </Link>
                                <a
                                    href="#trust"
                                    className="inline-flex items-center gap-2 rounded-lg border border-border px-5 py-3 text-sm font-medium transition-colors hover:bg-muted"
                                >
                                    Read the trust model
                                </a>
                            </div>

                            <div className="mt-8 max-w-xl rounded-xl border border-border bg-muted/40 p-4">
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <p className="text-xs font-medium tracking-[0.14em] text-muted-foreground uppercase">
                                        Demo access
                                    </p>
                                    <span className="rounded-full border border-border bg-background px-2 py-0.5 text-[10px] font-medium text-muted-foreground">
                                        Sandbox only
                                    </span>
                                </div>
                                <dl className="mt-3 grid gap-2 text-xs sm:grid-cols-2">
                                    <div>
                                        <dt className="text-muted-foreground">
                                            Buyer
                                        </dt>
                                        <dd className="mt-0.5 font-mono text-[11px]">
                                            buyer@dealflow.test
                                            <span className="text-muted-foreground">
                                                {' '}
                                                / password
                                            </span>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-muted-foreground">
                                            Seller
                                        </dt>
                                        <dd className="mt-0.5 font-mono text-[11px]">
                                            seller@dealflow.test
                                            <span className="text-muted-foreground">
                                                {' '}
                                                / password
                                            </span>
                                        </dd>
                                    </div>
                                </dl>
                            </div>

                            <dl className="mt-10 grid grid-cols-2 gap-x-5 gap-y-4 border-t border-border pt-6 text-xs sm:grid-cols-4">
                                {[
                                    'Real Gemini agents',
                                    'Deterministic limits',
                                    'Dual human approval',
                                    'PayPal settlement',
                                ].map((item) => (
                                    <div
                                        key={item}
                                        className="flex items-start gap-2"
                                    >
                                        <Check
                                            className="mt-0.5 size-3.5 shrink-0 text-emerald-700 dark:text-emerald-400"
                                            aria-hidden
                                        />
                                        <dt className="text-muted-foreground">
                                            {item}
                                        </dt>
                                    </div>
                                ))}
                            </dl>
                        </div>

                        <NegotiationPreview />
                    </section>

                    <section className="border-t border-border bg-muted/30">
                        <div className="mx-auto w-full max-w-6xl px-6 py-16 lg:py-24">
                            <div className="flex flex-wrap items-end justify-between gap-4">
                                <div className="max-w-2xl">
                                    <h2 className="text-3xl font-semibold tracking-tight text-balance sm:text-4xl">
                                        Services with the terms already spelled
                                        out.
                                    </h2>
                                    <p className="mt-4 text-base leading-7 text-muted-foreground">
                                        Browse published services, see the
                                        deliverables and delivery window, then
                                        start a structured request. No ratings
                                        theatre and no hidden placement.
                                    </p>
                                </div>
                                <div className="flex flex-wrap gap-2">
                                    <Link
                                        href="/services"
                                        className="inline-flex items-center gap-2 rounded-lg border border-border px-4 py-2 text-sm font-medium transition-colors hover:bg-muted"
                                    >
                                        Browse all services
                                        <ArrowRight
                                            className="size-4"
                                            aria-hidden
                                        />
                                    </Link>
                                    <Link
                                        href="/seller/services"
                                        className="inline-flex items-center gap-2 rounded-lg bg-foreground px-4 py-2 text-sm font-medium text-background transition-opacity hover:opacity-90"
                                    >
                                        Sell a service
                                    </Link>
                                </div>
                            </div>

                            {categories.length > 0 && (
                                <div className="mt-6 flex flex-wrap gap-2">
                                    {categories.map((category) => (
                                        <Link
                                            key={category.id}
                                            href={`/categories/${category.slug}`}
                                            className="rounded-full border border-border px-3 py-1.5 text-xs font-medium text-muted-foreground transition-colors hover:border-foreground hover:text-foreground"
                                        >
                                            {category.name}
                                        </Link>
                                    ))}
                                </div>
                            )}

                            {featuredServices.length > 0 ? (
                                <div className="mt-8 grid gap-x-8 gap-y-8 sm:grid-cols-2 lg:grid-cols-3">
                                    {featuredServices.map((service) => (
                                        <ServiceCard
                                            key={service.id}
                                            service={service}
                                        />
                                    ))}
                                </div>
                            ) : (
                                <div className="mt-8 rounded-xl border border-dashed p-8 text-center">
                                    <p className="font-medium">
                                        The marketplace is just getting started
                                    </p>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        Publish the first service and give
                                        buyers something concrete to request.
                                    </p>
                                </div>
                            )}
                        </div>
                    </section>

                    <section
                        id="trust"
                        className="border-t border-border bg-muted/30"
                    >
                        <div className="mx-auto w-full max-w-6xl px-6 py-16 lg:py-24">
                            <div className="max-w-2xl">
                                <h2 className="text-3xl font-semibold tracking-tight text-balance sm:text-4xl">
                                    The AI proposes. The system enforces.
                                </h2>
                                <p className="mt-4 text-base leading-7 text-muted-foreground">
                                    A language model is useful for finding
                                    agreement. It is the wrong authority for
                                    limits, consent, and money. Those stay
                                    deterministic and visible.
                                </p>
                            </div>

                            <div className="mt-10 overflow-hidden rounded-xl border border-border bg-card">
                                <div className="hidden grid-cols-[1.1fr_1fr_1.5fr] gap-6 border-b border-border bg-muted/50 px-5 py-3 text-[11px] font-medium tracking-[0.12em] text-muted-foreground uppercase md:grid">
                                    <span>Limit</span>
                                    <span>Enforced by</span>
                                    <span>What the agent sees</span>
                                </div>
                                {limits.map((limit) => (
                                    <div
                                        key={limit.name}
                                        className="grid gap-2 border-b border-border px-5 py-5 last:border-b-0 md:grid-cols-[1.1fr_1fr_1.5fr] md:items-start md:gap-6"
                                    >
                                        <div className="flex items-center gap-2 font-medium">
                                            <ShieldCheck
                                                className="size-4 text-emerald-700 dark:text-emerald-400"
                                                aria-hidden
                                            />
                                            {limit.name}
                                        </div>
                                        <div className="text-sm text-muted-foreground">
                                            {limit.owner}
                                        </div>
                                        <div className="text-sm leading-6 text-muted-foreground">
                                            {limit.detail}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </section>

                    <section id="how" className="border-t border-border">
                        <div className="mx-auto w-full max-w-6xl px-6 py-16 lg:py-24">
                            <div className="max-w-2xl">
                                <h2 className="text-3xl font-semibold tracking-tight text-balance sm:text-4xl">
                                    From opening offer to settled payment.
                                </h2>
                                <p className="mt-4 text-base leading-7 text-muted-foreground">
                                    Five steps. Two humans. One payment that
                                    only moves after the terms are agreed.
                                </p>
                            </div>

                            <ol className="mt-10 grid gap-px overflow-hidden rounded-xl border border-border bg-border md:grid-cols-5">
                                {steps.map((step, index) => (
                                    <li
                                        key={step.title}
                                        className="bg-card px-5 py-6"
                                    >
                                        <span className="font-mono text-[11px] text-muted-foreground">
                                            0{index + 1}
                                        </span>
                                        <h3 className="mt-4 text-sm font-semibold">
                                            {step.title}
                                        </h3>
                                        <p className="mt-2 text-sm leading-6 text-muted-foreground">
                                            {step.detail}
                                        </p>
                                    </li>
                                ))}
                            </ol>
                        </div>
                    </section>

                    <section
                        id="proof"
                        className="border-t border-border bg-muted/30"
                    >
                        <div className="mx-auto grid w-full max-w-6xl gap-10 px-6 py-16 lg:grid-cols-[0.85fr_1.15fr] lg:gap-16 lg:py-24">
                            <div>
                                <h2 className="text-3xl font-semibold tracking-tight text-balance sm:text-4xl">
                                    Every promise leaves a receipt.
                                </h2>
                                <p className="mt-4 text-base leading-7 text-muted-foreground">
                                    Proposals, approvals, payment events, and
                                    the final capture are written to an audit
                                    trail. The demo includes a real sandbox
                                    payment, not a mocked success screen.
                                </p>
                                <Link
                                    href={transactionsShow(1)}
                                    className="mt-6 inline-flex items-center gap-2 text-sm font-medium underline underline-offset-4 hover:text-emerald-700 dark:hover:text-emerald-400"
                                >
                                    See the paid deal
                                    <ArrowRight
                                        className="size-4"
                                        aria-hidden
                                    />
                                </Link>
                            </div>

                            <div className="overflow-hidden rounded-xl border border-border bg-card">
                                <div className="flex items-center justify-between border-b border-border px-5 py-3">
                                    <span className="flex items-center gap-2 text-xs font-medium">
                                        <FileCheck2
                                            className="size-4 text-emerald-700 dark:text-emerald-400"
                                            aria-hidden
                                        />
                                        Audit trail
                                    </span>
                                    <span className="font-mono text-[10px] text-muted-foreground">
                                        deal #1
                                    </span>
                                </div>
                                {audit.map(([event, value]) => (
                                    <div
                                        key={event}
                                        className="grid grid-cols-[1fr_auto] items-center gap-4 border-b border-border px-5 py-3.5 last:border-b-0"
                                    >
                                        <span className="font-mono text-[11px] text-muted-foreground">
                                            {event}
                                        </span>
                                        <span className="text-right text-xs font-medium">
                                            {value}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </section>

                    <section id="tools" className="border-t border-border">
                        <div className="mx-auto w-full max-w-6xl px-6 py-16 lg:py-24">
                            <div className="max-w-2xl">
                                <h2 className="text-3xl font-semibold tracking-tight text-balance sm:text-4xl">
                                    Built with tools you can inspect.
                                </h2>
                                <p className="mt-4 text-base leading-7 text-muted-foreground">
                                    The AI is real, the limits are code, and the
                                    payment is PayPal sandbox.
                                </p>
                            </div>

                            <dl className="mt-10 grid gap-x-10 border-t border-border sm:grid-cols-2">
                                {tools.map(([name, detail]) => (
                                    <div
                                        key={name}
                                        className="border-b border-border py-5"
                                    >
                                        <dt className="flex items-center gap-2 text-sm font-medium">
                                            <LockKeyhole
                                                className="size-3.5 text-muted-foreground"
                                                aria-hidden
                                            />
                                            {name}
                                        </dt>
                                        <dd className="mt-1.5 text-sm leading-6 text-muted-foreground">
                                            {detail}
                                        </dd>
                                    </div>
                                ))}
                            </dl>
                        </div>
                    </section>

                    <section className="border-t border-border bg-foreground text-background">
                        <div className="mx-auto grid w-full max-w-6xl gap-8 px-6 py-16 lg:grid-cols-[1.2fr_0.8fr] lg:items-end lg:py-20">
                            <div>
                                <h2 className="text-3xl font-semibold tracking-tight text-balance sm:text-4xl">
                                    Run the negotiation yourself.
                                </h2>
                                <p className="mt-4 max-w-xl text-sm leading-7 text-background/70">
                                    Log in as the buyer, open the product launch
                                    deal, and watch the agents work. Then switch
                                    to the seller to approve the terms and
                                    unlock PayPal checkout.
                                </p>
                            </div>

                            <div className="lg:text-right">
                                <div className="inline-flex flex-col items-start gap-3 rounded-xl border border-background/20 p-4 text-left">
                                    <div className="flex items-center gap-2 text-xs text-background/70">
                                        <Wallet
                                            className="size-4"
                                            aria-hidden
                                        />
                                        Sandbox credentials
                                    </div>
                                    <code className="font-mono text-[11px] leading-5">
                                        buyer@dealflow.test / password
                                        <br />
                                        seller@dealflow.test / password
                                    </code>
                                    <Link
                                        href={auth.user ? dashboard() : login()}
                                        className="inline-flex items-center gap-2 rounded-lg bg-background px-4 py-2 text-sm font-medium text-foreground transition-opacity hover:opacity-90"
                                    >
                                        {auth.user
                                            ? 'Open dashboard'
                                            : 'Open demo'}
                                        <ArrowRight
                                            className="size-4"
                                            aria-hidden
                                        />
                                    </Link>
                                </div>
                                <p className="mt-3 text-[11px] text-background/50">
                                    No real money moves. Sandbox only.
                                </p>
                            </div>
                        </div>
                    </section>
                </main>

                <footer className="border-t border-border">
                    <div className="mx-auto flex w-full max-w-6xl flex-col gap-4 px-6 py-8 text-xs text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex items-center gap-2">
                            <span className="flex size-6 items-center justify-center rounded-md bg-foreground text-background">
                                <Check className="size-3" aria-hidden />
                            </span>
                            <span>
                                DealFlow AI · Built for the PayPal AI Hackathon
                            </span>
                        </div>
                        <div className="flex flex-wrap items-center gap-5">
                            <a
                                href="https://github.com/Nhlanhla0001/dealflow-ai"
                                className="transition-colors hover:text-foreground"
                            >
                                GitHub
                            </a>
                            <Link
                                href={register()}
                                className="transition-colors hover:text-foreground"
                            >
                                Create account
                            </Link>
                            <a
                                href="https://github.com/Nhlanhla0001/dealflow-ai/blob/main/LICENSE"
                                className="transition-colors hover:text-foreground"
                            >
                                MIT License
                            </a>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
