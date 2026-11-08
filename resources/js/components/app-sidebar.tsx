import { Link, usePage } from '@inertiajs/react';
import {
    Bell,
    BookOpen,
    BriefcaseBusiness,
    Coins,
    FolderGit2,
    Handshake,
    LayoutGrid,
    Package,
    ShieldAlert,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as notificationsIndex } from '@/routes/notifications';
import { index as transactionsIndex } from '@/routes/transactions';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Transactions',
        href: transactionsIndex(),
        icon: Handshake,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/Nhlanhla0001/dealflow-ai',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://github.com/Nhlanhla0001/dealflow-ai#readme',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    const { unreadNotifications = 0, auth } = usePage<{
        unreadNotifications?: number;
        auth?: {
            user?: {
                role?: string;
            } | null;
        };
    }>().props;

    const isSeller =
        auth?.user?.role === 'seller' ||
        auth?.user?.role === 'both' ||
        auth?.user?.role === 'admin';
    const isAdmin = auth?.user?.role === 'admin';

    const navItems: NavItem[] = [...mainNavItems];

    if (isSeller) {
        navItems.push({
            title: 'My services',
            href: '/seller/services',
            icon: BriefcaseBusiness,
        });
    }

    if (isAdmin) {
        navItems.push({
            title: 'Admin',
            href: '/admin',
            icon: ShieldAlert,
        });
    }

    navItems.push(
        {
            title: 'Orders',
            href: '/orders',
            icon: Package,
        },
        {
            title: 'AI usage',
            href: '/ai/usage',
            icon: Coins,
        },
        {
            title: 'Notifications',
            href: notificationsIndex(),
            icon: Bell,
            badge: unreadNotifications,
        },
    );

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={navItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
