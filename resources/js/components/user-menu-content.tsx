import { Link, router } from '@inertiajs/react';
import { LogOut, Monitor, Moon, Settings, Sun } from 'lucide-react';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuSeparator,
    DropdownMenuSub,
    DropdownMenuSubContent,
    DropdownMenuSubTrigger,
} from '@/components/ui/dropdown-menu';
import { UserInfo } from '@/components/user-info';
import { useAppearance } from '@/hooks/use-appearance';
import { useMobileNavigation } from '@/hooks/use-mobile-navigation';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';
import type { User } from '@/types';

type Props = {
    user: User;
};

export function UserMenuContent({ user }: Props) {
    const cleanup = useMobileNavigation();
    const { appearance, updateAppearance } = useAppearance();

    const handleLogout = () => {
        cleanup();
        router.flushAll();
    };

    return (
        <>
            <DropdownMenuLabel className="p-0 font-normal">
                <div className="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                    <UserInfo user={user} showEmail={true} />
                </div>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuGroup>
                <DropdownMenuItem asChild>
                    <Link
                        className="block w-full cursor-pointer"
                        href={edit()}
                        prefetch
                        onClick={cleanup}
                    >
                        <Settings className="mr-2" />
                        Settings
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuSub>
                    <DropdownMenuSubTrigger>
                        <Sun className="mr-2 size-4 dark:hidden" />
                        <Moon className="mr-2 hidden size-4 dark:block" />
                        Theme
                    </DropdownMenuSubTrigger>
                    <DropdownMenuSubContent>
                        <DropdownMenuRadioGroup
                            value={appearance}
                            onValueChange={(value) =>
                                updateAppearance(
                                    value as 'light' | 'dark' | 'system',
                                )
                            }
                        >
                            <DropdownMenuRadioItem value="light">
                                <Sun className="mr-2 size-4" />
                                Light
                            </DropdownMenuRadioItem>
                            <DropdownMenuRadioItem value="dark">
                                <Moon className="mr-2 size-4" />
                                Dark
                            </DropdownMenuRadioItem>
                            <DropdownMenuRadioItem value="system">
                                <Monitor className="mr-2 size-4" />
                                System
                            </DropdownMenuRadioItem>
                        </DropdownMenuRadioGroup>
                    </DropdownMenuSubContent>
                </DropdownMenuSub>
            </DropdownMenuGroup>
            <DropdownMenuSeparator />
            <DropdownMenuItem asChild>
                <Link
                    className="block w-full cursor-pointer"
                    href={logout()}
                    as="button"
                    onClick={handleLogout}
                    data-test="logout-button"
                >
                    <LogOut className="mr-2" />
                    Log out
                </Link>
            </DropdownMenuItem>
        </>
    );
}
