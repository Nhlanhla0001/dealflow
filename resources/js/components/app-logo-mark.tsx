import type { HTMLAttributes } from 'react';

import AppLogoIcon from '@/components/app-logo-icon';
import { cn } from '@/lib/utils';

/**
 * The tile version of the DealFlow mark, for standalone brand placement.
 */
export default function AppLogoMark({
    className,
    ...props
}: HTMLAttributes<HTMLDivElement>) {
    return (
        <div
            className={cn(
                'flex items-center justify-center rounded-lg bg-foreground text-background',
                className,
            )}
            {...props}
        >
            <AppLogoIcon className="size-[62%]" />
        </div>
    );
}
