import { Head, Link } from '@inertiajs/react';
import { ServiceCard } from '@/components/marketplace/service-card';
import { MarketplaceLayout } from '@/layouts/marketplace-layout';
import type { MarketplaceService } from '@/types';

type Props = {
    seller: {
        id: number;
        name: string;
        created_at: string;
    };
    services: MarketplaceService[];
};

export default function MarketplaceSeller({ seller, services }: Props) {
    return (
        <MarketplaceLayout>
            <Head title={`${seller.name} — DealFlow AI`}>
                <meta
                    name="description"
                    content={`Services published by ${seller.name} on DealFlow AI.`}
                />
                <link rel="canonical" href={`/sellers/${seller.id}`} />
            </Head>

            <div className="mx-auto w-full max-w-6xl px-6 py-12 lg:py-16">
                <nav
                    aria-label="Breadcrumb"
                    className="flex items-center gap-1.5 text-xs text-muted-foreground"
                >
                    <Link href="/" className="hover:text-foreground">
                        Home
                    </Link>
                    <span aria-hidden>/</span>
                    <Link href="/services" className="hover:text-foreground">
                        Services
                    </Link>
                    <span aria-hidden>/</span>
                    <span>{seller.name}</span>
                </nav>

                <div className="mt-8 max-w-2xl">
                    <h1 className="text-3xl font-semibold tracking-tight sm:text-4xl">
                        {seller.name}
                    </h1>
                    <p className="mt-2 text-sm text-muted-foreground">
                        {services.length} published{' '}
                        {services.length === 1 ? 'service' : 'services'}
                        {seller.created_at
                            ? ` · Member since ${new Date(seller.created_at).toLocaleDateString('en-US', { month: 'long', year: 'numeric' })}`
                            : ''}
                    </p>
                </div>

                {services.length === 0 ? (
                    <div className="mt-10 rounded-xl border border-dashed p-10 text-center">
                        <p className="font-medium">No published services</p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            This seller has not published a service yet.
                        </p>
                    </div>
                ) : (
                    <div className="mt-10 grid gap-x-8 gap-y-8 sm:grid-cols-2 lg:grid-cols-3">
                        {services.map((service) => (
                            <ServiceCard key={service.id} service={service} />
                        ))}
                    </div>
                )}
            </div>
        </MarketplaceLayout>
    );
}
