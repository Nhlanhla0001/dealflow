import type { SVGAttributes } from 'react';

import BrandMark from '@/components/brand-mark';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return <BrandMark {...props} />;
}
