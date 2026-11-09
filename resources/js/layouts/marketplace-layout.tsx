import { Link, usePage } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';

import AppLogoIcon from '@/components/app-logo-icon';
import type { Auth } from '@/types';

export function MarketplaceLayout({ children }: { children: React.ReactNode }) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const isSeller =
        auth.user?.role === 'seller' ||
        auth.user?.role === 'both' ||
        auth.user?.role === 'admin';

    return (
        <div className="min-h-screen bg-background text-foreground">
            <header className="border-b border-border">
                <div className="mx-auto flex h-16 w-full max-w-6xl items-center justify-between gap-4 px-6">
                    <Link
                        href="/"
                        className="flex items-center gap-2.5 text-sm font-semibold"
                    >
                        <span className="flex size-8 items-center justify-center rounded-lg bg-foreground text-background">
                            <AppLogoIcon className="size-5" />
                        </span>
                        <span>DealFlow</span>
                    </Link>

                    <nav className="hidden items-center gap-7 text-sm text-muted-foreground md:flex">
                        <Link
                            href="/services"
                            className="transition-colors hover:text-foreground"
                        >
                            Services
                        </Link>
                        {isSeller ? (
                            <Link
                                href="/seller/services"
                                className="transition-colors hover:text-foreground"
                            >
                                Sell a service
                            </Link>
                        ) : (
                            <Link
                                href="/register"
                                className="transition-colors hover:text-foreground"
                            >
                                Sell a service
                            </Link>
                        )}
                        <Link
                            href="/#how"
                            className="transition-colors hover:text-foreground"
                        >
                            How it works
                        </Link>
                    </nav>

                    <div className="flex items-center gap-2">
                        {auth.user ? (
                            <Link
                                href="/dashboard"
                                className="inline-flex items-center gap-2 rounded-lg bg-foreground px-4 py-2 text-sm font-medium text-background transition-opacity hover:opacity-90"
                            >
                                Dashboard
                                <ArrowRight className="size-4" aria-hidden />
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href="/login"
                                    className="hidden rounded-lg px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground sm:inline-flex"
                                >
                                    Log in
                                </Link>
                                <Link
                                    href="/register"
                                    className="inline-flex items-center gap-2 rounded-lg bg-foreground px-4 py-2 text-sm font-medium text-background transition-opacity hover:opacity-90"
                                >
                                    Get started
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

            <main>{children}</main>

            <footer className="border-t border-border">
                <div className="mx-auto flex w-full max-w-6xl flex-col gap-4 px-6 py-8 text-xs text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
                    <p>
                        DealFlow · Services, structured negotiation, PayPal
                        settlement
                    </p>
                    <div className="flex flex-wrap items-center gap-5">
                        <a
                            href="https://github.com/Nhlanhla0001/dealflow-ai"
                            className="transition-colors hover:text-foreground"
                        >
                            GitHub
                        </a>
                        <a
                            href="https://github.com/Nhlanhla0001/dealflow-ai/blob/main/LICENSE"
                            className="transition-colors hover:text-foreground"
                        >
                            MIT License
                        </a>
                        <a
                            href="/sitemap.xml"
                            className="transition-colors hover:text-foreground"
                        >
                            Sitemap
                        </a>
                    </div>
                </div>
            </footer>
        </div>
    );
}
