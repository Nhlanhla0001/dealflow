export type TransactionState =
    | 'draft'
    | 'negotiating'
    | 'agreement_proposed'
    | 'awaiting_approvals'
    | 'approved'
    | 'payment_pending'
    | 'paid'
    | 'cancelled'
    | 'failed';

export type OfferStatus = 'active' | 'accepted' | 'rejected';

export type DealParty = {
    id: number;
    name: string;
    email?: string;
};

export type DealOffer = {
    id: number;
    transaction_id: number;
    seller_id: number;
    price_minor: number;
    currency: string;
    delivery_days: number;
    terms: string | null;
    status: OfferStatus;
    seller?: DealParty;
};

export type DealMessage = {
    id: number;
    agent: 'buyer' | 'seller';
    round: number;
    action: string;
    price_minor: number | null;
    currency: string | null;
    delivery_days: number | null;
    reason: string | null;
    created_at: string;
};

export type DealNegotiationSession = {
    id: number;
    status: string;
    round: number;
    max_rounds: number;
    messages?: DealMessage[];
};

export type DealAgreement = {
    id: number;
    version: number;
    price_minor: number;
    currency: string;
    delivery_days: number;
    status: string;
    approvals?: {
        id: number;
        user_id: number;
        role: string;
    }[];
    created_at: string;
};

export type DealPaymentOrder = {
    id: number;
    status: string;
    amount_minor: number;
    currency: string;
    paypal_order_id: string | null;
    paypal_approval_url: string | null;
    capture_id: string | null;
    created_at: string;
};

export type DealAuditLog = {
    id: number;
    action: string;
    metadata: Record<string, unknown> | null;
    created_at: string;
    user?: {
        id: number;
        name: string;
    } | null;
};

export type DealTransaction = {
    id: number;
    buyer_id: number;
    seller_id: number;
    title: string;
    description: string;
    currency: string;
    budget_minor: number;
    seller_floor_minor: number | null;
    seller_offer_minor: number | null;
    delivery_days: number;
    seller_delivery_days: number | null;
    state: TransactionState;
    created_at: string;
    updated_at: string;
    buyer?: DealParty;
    seller?: DealParty;
    offer?: DealOffer | null;
    negotiation_session?: DealNegotiationSession | null;
    latest_agreement?: DealAgreement | null;
    payment_orders?: DealPaymentOrder[];
    audit_logs?: DealAuditLog[];
};

export type DealStats = {
    open: number;
    negotiating: number;
    awaiting_approvals: number;
    paid: number;
};

export type AppNotificationData = {
    type: string;
    title: string;
    message: string;
    transaction_id?: number;
    agreement_id?: number;
    url: string;
};

export type AppNotification = {
    id: string;
    data: AppNotificationData;
    read_at: string | null;
    created_at: string | null;
};
