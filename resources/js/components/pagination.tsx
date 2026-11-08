import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types';

type PaginationLink = Paginated<unknown>['links'][number];

const base =
    'inline-flex h-10 min-w-10 items-center justify-center gap-1 rounded-md border px-2 text-xs font-medium tabular-nums transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background sm:h-8 sm:min-w-8';
const idle =
    'border-border text-muted-foreground hover:bg-muted hover:text-foreground';
const active = 'border-foreground bg-foreground text-background';
const disabled =
    'pointer-events-none border-transparent text-muted-foreground/50';

function PageLink({
    link,
    direction,
}: {
    link: PaginationLink;
    direction?: 'previous' | 'next';
}) {
    const content =
        direction === 'previous' ? (
            <>
                <ChevronLeft className="size-3.5" aria-hidden />
                <span className="sr-only">Previous</span>
            </>
        ) : direction === 'next' ? (
            <>
                <span className="sr-only">Next</span>
                <ChevronRight className="size-3.5" aria-hidden />
            </>
        ) : (
            link.label
        );

    if (link.url === null) {
        return (
            <span aria-disabled="true" className={cn(base, disabled)}>
                {content}
            </span>
        );
    }

    return (
        <Link
            href={link.url}
            preserveScroll
            preserveState
            aria-current={link.active ? 'page' : undefined}
            className={cn(base, link.active ? active : idle)}
        >
            {content}
        </Link>
    );
}

export function Pagination({
    paginator,
    itemLabel = 'items',
}: {
    paginator: Paginated<unknown>;
    itemLabel?: string;
}) {
    if (paginator.last_page <= 1 || paginator.links.length < 2) {
        return null;
    }

    const first = paginator.links[0];
    const last = paginator.links[paginator.links.length - 1];
    const pages = paginator.links.slice(1, -1);

    return (
        <nav
            aria-label="Pagination"
            className="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-border pt-4"
        >
            <p className="text-xs text-muted-foreground">
                Showing{' '}
                <span className="font-medium text-foreground tabular-nums">
                    {paginator.from ?? 0}–{paginator.to ?? 0}
                </span>{' '}
                of{' '}
                <span className="font-medium text-foreground tabular-nums">
                    {paginator.total}
                </span>{' '}
                {itemLabel}
            </p>

            <div className="flex items-center gap-1">
                <PageLink link={first} direction="previous" />
                {pages.map((link, index) =>
                    link.label === '...' ? (
                        <span
                            key={`gap-${index}`}
                            aria-hidden
                            className="px-1 text-xs text-muted-foreground"
                        >
                            …
                        </span>
                    ) : (
                        <PageLink key={`${link.label}-${index}`} link={link} />
                    ),
                )}
                <PageLink link={last} direction="next" />
            </div>
        </nav>
    );
}
