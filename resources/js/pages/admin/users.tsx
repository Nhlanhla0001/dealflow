import { Head, router } from '@inertiajs/react';
import { Pagination } from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { Paginated, User } from '@/types';

const roles = ['buyer', 'seller', 'both', 'admin'];

export default function AdminUsers({
    users,
}: {
    users: Paginated<
        User & {
            services_count: number;
            buyer_transactions_count: number;
            seller_transactions_count: number;
        }
    >;
}) {
    const changeRole = (user: User, role: string) => {
        router.patch(
            `/admin/users/${user.id}/role`,
            { role },
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title="Admin — Users" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div>
                    <p className="text-xs font-medium tracking-[0.22em] text-muted-foreground uppercase">
                        Administration
                    </p>
                    <h1 className="mt-2 text-2xl font-semibold tracking-tight">
                        Users
                    </h1>
                    <p className="mt-1 max-w-2xl text-sm text-muted-foreground">
                        Roles and account activity. A user cannot change their
                        own role here.
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Accounts</CardTitle>
                        <CardDescription>{users.total} users.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left text-xs tracking-wide text-muted-foreground uppercase">
                                        <th className="py-3 pr-4 font-medium">
                                            User
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Role
                                        </th>
                                        <th className="hidden px-4 py-3 font-medium md:table-cell">
                                            Services
                                        </th>
                                        <th className="hidden py-3 pl-4 font-medium md:table-cell">
                                            Deals
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {users.data.map((user) => (
                                        <tr
                                            key={user.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="py-3 pr-4">
                                                <p className="font-medium">
                                                    {user.name}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {user.email}
                                                </p>
                                                <p className="mt-1 text-xs text-muted-foreground md:hidden">
                                                    {user.services_count}{' '}
                                                    services ·{' '}
                                                    {
                                                        user.buyer_transactions_count
                                                    }{' '}
                                                    buying ·{' '}
                                                    {
                                                        user.seller_transactions_count
                                                    }{' '}
                                                    selling
                                                </p>
                                            </td>
                                            <td className="px-4 py-3">
                                                <select
                                                    value={user.role}
                                                    onChange={(event) =>
                                                        changeRole(
                                                            user,
                                                            event.target.value,
                                                        )
                                                    }
                                                    className="h-8 rounded-md border border-input bg-transparent px-2 text-xs"
                                                >
                                                    {roles.map((role) => (
                                                        <option
                                                            key={role}
                                                            value={role}
                                                        >
                                                            {role}
                                                        </option>
                                                    ))}
                                                </select>
                                            </td>
                                            <td className="hidden px-4 py-3 md:table-cell">
                                                <Badge variant="outline">
                                                    {user.services_count}
                                                </Badge>
                                            </td>
                                            <td className="hidden py-3 pl-4 text-muted-foreground md:table-cell">
                                                {user.buyer_transactions_count}{' '}
                                                buying ·{' '}
                                                {user.seller_transactions_count}{' '}
                                                selling
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <Pagination paginator={users} itemLabel="users" />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

AdminUsers.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Admin', href: '/admin' },
        { title: 'Users', href: '/admin/users' },
    ],
};
