export type PricingType = 'fixed' | 'hourly' | 'starting_at';

export type ServiceStatus = 'draft' | 'published' | 'unpublished' | 'archived';

export type MarketplaceCategory = {
    id: number;
    name: string;
    slug: string;
    description?: string | null;
};

export type MarketplaceService = {
    id: number;
    seller_id: number;
    category_id: number;
    title: string;
    slug: string;
    description: string;
    deliverables: string[];
    pricing_type: PricingType;
    price_minor: number;
    currency: string;
    includes: string | null;
    delivery_days: number;
    revisions: number;
    exclusions: string | null;
    requirements: string | null;
    images: string[] | null;
    supporting_info: string | null;
    negotiation_enabled: boolean;
    status: ServiceStatus;
    published_at: string | null;
    created_at?: string;
    updated_at?: string;
    seller?: {
        id: number;
        name: string;
        created_at?: string;
    };
    category?: MarketplaceCategory;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
};

export type MarketplaceFilters = {
    q: string;
    category: string;
    price_min: string;
    price_max: string;
    sort: 'newest' | 'price_asc' | 'price_desc';
};
