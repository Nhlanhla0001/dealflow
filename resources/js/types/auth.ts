export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    role: 'buyer' | 'seller' | 'both' | 'admin';
    headline?: string | null;
    bio?: string | null;
    timezone?: string;
    notification_email?: boolean;
    notification_in_app?: boolean;
    onboarding_completed_at?: string | null;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};

export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
