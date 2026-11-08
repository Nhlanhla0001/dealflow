import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight, Check, Clock3, Repeat2, ShieldCheck } from 'lucide-react';
import { ServiceCard } from '@/components/marketplace/service-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { MarketplaceLayout } from '@/layouts/marketplace-layout';
import { formatMoney } from '@/lib/format';
import type { Auth, MarketplaceService } from '@/types';

type Props = {
    service: MarketplaceService;
    related: MarketplaceService[];
    canonical: string;
};

export default function MarketplaceShow({
    service,
    related,
    canonical,
}: Props) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const canBuy =
        auth.user?.role === 'buyer' ||
        auth.user?.role === 'both' ||
        auth.user?.role === 'admin';
    const images = service.images ?? [];

    const jsonLd = {
        '@context': 'https://schema.org',
        '@type': 'Service',
        name: service.title,
        description: service.description,
        category: service.category?.name,
        provider: service.seller
            ? {
                  '@type': 'Person',
                  name: service.seller.name,
              }
            : undefined,
        offers: {
            '@type': 'Offer',
            priceCurrency: service.currency,
            price: (service.price_minor / 100).toFixed(2),
            availability: 'https://schema.org/InStock',
            url: canonical,
        },
    };

    return (
        <MarketplaceLayout>
            <Head title={service.title}>
                <meta
                    name="description"
                    content={service.description.slice(0, 155)}
                />
                <link rel="canonical" href={canonical} />
            </Head>

            <script
                type="application/ld+json"
                dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd) }}
            />

            <div className="mx-auto w-full max-w-6xl px-6 py-10 lg:py-14">
                <nav
                    aria-label="Breadcrumb"
                    className="flex flex-wrap items-center gap-1.5 text-xs text-muted-foreground"
                >
                    <Link href="/" className="hover:text-foreground">
                        Home
                    </Link>
                    <span aria-hidden>/</span>
                    <Link href="/services" className="hover:text-foreground">
                        Services
                    </Link>
                    {service.category && (
                        <>
                            <span aria-hidden>/</span>
                            <Link
                                href={`/categories/${service.category.slug}`}
                                className="hover:text-foreground"
                            >
                                {service.category.name}
                            </Link>
                        </>
                    )}
                </nav>

                <div className="mt-8 grid gap-10 lg:grid-cols-[1.45fr_0.75fr] lg:gap-16">
                    <div className="min-w-0">
                        <div className="flex flex-wrap items-center gap-2">
                            {service.category && (
                                <Badge variant="outline">
                                    {service.category.name}
                                </Badge>
                            )}
                            <Badge variant="secondary">
                                {service.negotiation_enabled
                                    ? 'Negotiation available'
                                    : 'Fixed terms'}
                            </Badge>
                        </div>

                        <h1 className="mt-4 text-3xl font-semibold tracking-tight text-balance sm:text-4xl">
                            {service.title}
                        </h1>

                        {service.seller && (
                            <p className="mt-3 text-sm text-muted-foreground">
                                By{' '}
                                <Link
                                    href={`/sellers/${service.seller.id}`}
                                    className="font-medium text-foreground underline underline-offset-4"
                                >
                                    {service.seller.name}
                                </Link>
                            </p>
                        )}

                        <p className="mt-6 text-base leading-7 whitespace-pre-line text-muted-foreground">
                            {service.description}
                        </p>

                        {images.length > 0 && (
                            <div className="mt-8 grid gap-3 sm:grid-cols-2">
                                {images.map((image) => (
                                    <img
                                        key={image}
                                        src={image}
                                        alt={`${service.title} preview`}
                                        className="h-56 w-full rounded-xl border border-border object-cover"
                                        loading="lazy"
                                    />
                                ))}
                            </div>
                        )}

                        <section className="mt-10 border-t border-border pt-8">
                            <h2 className="text-lg font-semibold">
                                What you get
                            </h2>
                            <ul className="mt-4 grid gap-2 sm:grid-cols-2">
                                {service.deliverables.map((item) => (
                                    <li
                                        key={item}
                                        className="flex items-start gap-2 text-sm leading-6"
                                    >
                                        <Check
                                            className="mt-1 size-4 shrink-0 text-emerald-700 dark:text-emerald-400"
                                            aria-hidden
                                        />
                                        {item}
                                    </li>
                                ))}
                            </ul>
                        </section>

                        <div className="mt-8 grid gap-4 sm:grid-cols-2">
                            {service.includes && (
                                <section className="rounded-xl border border-border p-4">
                                    <h2 className="text-sm font-semibold">
                                        Included
                                    </h2>
                                    <p className="mt-2 text-sm leading-6 whitespace-pre-line text-muted-foreground">
                                        {service.includes}
                                    </p>
                                </section>
                            )}
                            {service.exclusions && (
                                <section className="rounded-xl border border-border p-4">
                                    <h2 className="text-sm font-semibold">
                                        Not included
                                    </h2>
                                    <p className="mt-2 text-sm leading-6 whitespace-pre-line text-muted-foreground">
                                        {service.exclusions}
                                    </p>
                                </section>
                            )}
                        </div>

                        {service.requirements && (
                            <section className="mt-8 rounded-xl border border-border p-4">
                                <h2 className="text-sm font-semibold">
                                    What the seller needs from you
                                </h2>
                                <p className="mt-2 text-sm leading-6 whitespace-pre-line text-muted-foreground">
                                    {service.requirements}
                                </p>
                            </section>
                        )}

                        {service.supporting_info && (
                            <section className="mt-8">
                                <h2 className="text-lg font-semibold">
                                    Supporting information
                                </h2>
                                <p className="mt-2 text-sm leading-6 whitespace-pre-line text-muted-foreground">
                                    {service.supporting_info}
                                </p>
                            </section>
                        )}
                    </div>

                    <aside className="lg:sticky lg:top-6 lg:self-start">
                        <div className="rounded-xl border border-border bg-card p-5">
                            <p className="text-2xl font-semibold tracking-tight">
                                {formatMoney(
                                    service.price_minor,
                                    service.currency,
                                )}
                            </p>
                            <p className="mt-1 text-xs text-muted-foreground">
                                {service.pricing_type === 'hourly'
                                    ? 'Per hour'
                                    : service.pricing_type === 'starting_at'
                                      ? 'Starting price'
                                      : 'Fixed price'}
                            </p>

                            <dl className="mt-5 space-y-3 border-t border-border pt-4 text-sm">
                                <div className="flex items-center justify-between gap-3">
                                    <dt className="flex items-center gap-2 text-muted-foreground">
                                        <Clock3
                                            className="size-3.5"
                                            aria-hidden
                                        />
                                        Delivery
                                    </dt>
                                    <dd className="font-medium">
                                        {service.delivery_days} days
                                    </dd>
                                </div>
                                <div className="flex items-center justify-between gap-3">
                                    <dt className="flex items-center gap-2 text-muted-foreground">
                                        <Repeat2
                                            className="size-3.5"
                                            aria-hidden
                                        />
                                        Revisions
                                    </dt>
                                    <dd className="font-medium">
                                        {service.revisions}
                                    </dd>
                                </div>
                            </dl>

                            <div className="mt-5 border-t border-border pt-4">
                                {canBuy ? (
                                    <Button asChild className="w-full">
                                        <Link
                                            href={`/buyer/requests/new?service=${service.slug}`}
                                        >
                                            Request this service
                                            <ArrowRight
                                                className="size-4"
                                                aria-hidden
                                            />
                                        </Link>
                                    </Button>
                                ) : auth.user ? (
                                    <p className="text-sm leading-6 text-muted-foreground">
                                        Seller accounts can publish services.
                                        Use a buyer account to request this
                                        service.
                                    </p>
                                ) : (
                                    <Button asChild className="w-full">
                                        <Link href="/login">
                                            Log in to request
                                            <ArrowRight
                                                className="size-4"
                                                aria-hidden
                                            />
                                        </Link>
                                    </Button>
                                )}
                            </div>

                            <p className="mt-3 flex items-start gap-2 text-xs leading-5 text-muted-foreground">
                                <ShieldCheck
                                    className="mt-0.5 size-3.5 shrink-0 text-emerald-700 dark:text-emerald-400"
                                    aria-hidden
                                />
                                You approve the final terms before payment.
                            </p>
                        </div>
                    </aside>
                </div>

                {related.length > 0 && (
                    <section className="mt-16 border-t border-border pt-10">
                        <div className="flex items-end justify-between gap-4">
                            <h2 className="text-xl font-semibold tracking-tight">
                                More in{' '}
                                {service.category?.name ?? 'this category'}
                            </h2>
                            <Link
                                href="/services"
                                className="text-sm font-medium underline underline-offset-4"
                            >
                                Browse all
                            </Link>
                        </div>
                        <div className="mt-6 grid gap-x-8 gap-y-8 sm:grid-cols-2 lg:grid-cols-3">
                            {related.map((item) => (
                                <ServiceCard key={item.id} service={item} />
                            ))}
                        </div>
                    </section>
                )}
            </div>
        </MarketplaceLayout>
    );
}
