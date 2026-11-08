import { Head, Link, router } from '@inertiajs/react';
import { Search, SlidersHorizontal } from 'lucide-react';
import { useState } from 'react';
import { ServiceCard } from '@/components/marketplace/service-card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { MarketplaceLayout } from '@/layouts/marketplace-layout';
import type {
    MarketplaceCategory,
    MarketplaceFilters,
    MarketplaceService,
    Paginated,
} from '@/types';

type Props = {
    services: Paginated<MarketplaceService>;
    categories: MarketplaceCategory[];
    filters: MarketplaceFilters;
    selectedCategory: MarketplaceCategory | null;
};

export default function MarketplaceIndex({
    services,
    categories,
    filters,
    selectedCategory,
}: Props) {
    const [query, setQuery] = useState(filters.q ?? '');
    const [priceMin, setPriceMin] = useState(filters.price_min ?? '');
    const [priceMax, setPriceMax] = useState(filters.price_max ?? '');
    const [sort, setSort] = useState(filters.sort ?? 'newest');

    const applyFilters = (event?: React.FormEvent) => {
        event?.preventDefault();

        router.get(
            '/services',
            {
                q: query || undefined,
                category: selectedCategory?.slug,
                price_min: priceMin || undefined,
                price_max: priceMax || undefined,
                sort,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const title = selectedCategory
        ? `${selectedCategory.name} services`
        : 'Services';

    return (
        <MarketplaceLayout>
            <Head title={`${title} — DealFlow AI`}>
                <meta
                    name="description"
                    content="Discover services with clear deliverables, delivery windows, and structured negotiation. Approve the terms before you pay."
                />
                <link
                    rel="canonical"
                    href={
                        selectedCategory
                            ? `/categories/${selectedCategory.slug}`
                            : '/services'
                    }
                />
            </Head>

            <div className="mx-auto w-full max-w-6xl px-6 py-12 lg:py-16">
                <div className="max-w-2xl">
                    <h1 className="text-3xl font-semibold tracking-tight text-balance sm:text-4xl">
                        {selectedCategory
                            ? `${selectedCategory.name} services`
                            : 'Find a service. Agree the terms. Pay when it’s ready.'}
                    </h1>
                    <p className="mt-3 text-base leading-7 text-muted-foreground">
                        Every listing states the deliverables, price, delivery
                        window, and whether negotiation is available. You
                        approve the final terms.
                    </p>
                </div>

                <form
                    onSubmit={applyFilters}
                    className="mt-8 rounded-xl border border-border bg-card p-4"
                >
                    <div className="grid gap-3 md:grid-cols-[1.5fr_0.8fr_0.8fr_0.8fr_auto]">
                        <label className="relative block">
                            <span className="sr-only">Search services</span>
                            <Search
                                className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                                aria-hidden
                            />
                            <Input
                                value={query}
                                onChange={(event) =>
                                    setQuery(event.target.value)
                                }
                                placeholder="Search services"
                                className="pl-9"
                            />
                        </label>
                        <label>
                            <span className="sr-only">Minimum price</span>
                            <Input
                                value={priceMin}
                                onChange={(event) =>
                                    setPriceMin(event.target.value)
                                }
                                type="number"
                                min="0"
                                placeholder="Min $"
                            />
                        </label>
                        <label>
                            <span className="sr-only">Maximum price</span>
                            <Input
                                value={priceMax}
                                onChange={(event) =>
                                    setPriceMax(event.target.value)
                                }
                                type="number"
                                min="0"
                                placeholder="Max $"
                            />
                        </label>
                        <label>
                            <span className="sr-only">Sort services</span>
                            <select
                                value={sort}
                                onChange={(event) =>
                                    setSort(
                                        event.target
                                            .value as MarketplaceFilters['sort'],
                                    )
                                }
                                className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            >
                                <option value="newest">Newest</option>
                                <option value="price_asc">
                                    Price: low to high
                                </option>
                                <option value="price_desc">
                                    Price: high to low
                                </option>
                            </select>
                        </label>
                        <Button type="submit">
                            <SlidersHorizontal className="size-4" aria-hidden />
                            Apply
                        </Button>
                    </div>
                </form>

                <div className="mt-6 flex flex-wrap gap-2">
                    <Link
                        href="/services"
                        className={`rounded-full border px-3 py-1.5 text-xs font-medium transition-colors ${
                            selectedCategory
                                ? 'border-border text-muted-foreground hover:text-foreground'
                                : 'border-foreground bg-foreground text-background'
                        }`}
                    >
                        All services
                    </Link>
                    {categories.map((category) => (
                        <Link
                            key={category.id}
                            href={`/categories/${category.slug}`}
                            className={`rounded-full border px-3 py-1.5 text-xs font-medium transition-colors ${
                                selectedCategory?.id === category.id
                                    ? 'border-foreground bg-foreground text-background'
                                    : 'border-border text-muted-foreground hover:text-foreground'
                            }`}
                        >
                            {category.name}
                        </Link>
                    ))}
                </div>

                <div className="mt-8 flex items-center justify-between gap-4">
                    <p className="text-sm text-muted-foreground">
                        {services.total}{' '}
                        {services.total === 1 ? 'service' : 'services'}
                    </p>
                    {selectedCategory && (
                        <p className="text-sm text-muted-foreground">
                            {selectedCategory.description}
                        </p>
                    )}
                </div>

                <h2 className="sr-only">Services</h2>

                {services.data.length === 0 ? (
                    <div className="mt-8 rounded-xl border border-dashed p-10 text-center">
                        <p className="font-medium">No services found</p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Try a different search or category.
                        </p>
                        <Button asChild variant="outline" className="mt-4">
                            <Link href="/services">Clear filters</Link>
                        </Button>
                    </div>
                ) : (
                    <div className="mt-4 grid gap-x-8 gap-y-8 sm:grid-cols-2 lg:grid-cols-3">
                        {services.data.map((service) => (
                            <ServiceCard key={service.id} service={service} />
                        ))}
                    </div>
                )}

                {services.last_page > 1 && (
                    <nav
                        aria-label="Service pagination"
                        className="mt-10 flex flex-wrap items-center justify-center gap-1"
                    >
                        {services.links.map((link, index) => {
                            const label = link.label
                                .replace('&laquo;', '')
                                .replace('&raquo;', '')
                                .replace('Previous', 'Prev')
                                .trim();

                            if (!link.url) {
                                return (
                                    <span
                                        key={index}
                                        className="rounded-md px-3 py-1.5 text-sm text-muted-foreground/50"
                                    >
                                        {label}
                                    </span>
                                );
                            }

                            return (
                                <Link
                                    key={index}
                                    href={link.url}
                                    preserveScroll
                                    className={`rounded-md px-3 py-1.5 text-sm transition-colors ${
                                        link.active
                                            ? 'bg-foreground text-background'
                                            : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                                    }`}
                                >
                                    {label}
                                </Link>
                            );
                        })}
                    </nav>
                )}
            </div>
        </MarketplaceLayout>
    );
}
