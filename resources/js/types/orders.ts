export type OrderStatus =
    | 'paid'
    | 'in_progress'
    | 'revision_requested'
    | 'delivered'
    | 'completed'
    | 'disputed'
    | 'cancelled';

export type OrderEvent = {
    id: number;
    type: string;
    message: string | null;
    metadata: Record<string, unknown> | null;
    created_at: string;
    user?: {
        id: number;
        name: string;
    } | null;
};

export type DealOrder = {
    id: number;
    transaction_id: number;
    buyer_id: number;
    seller_id: number;
    amount_minor: number;
    currency: string;
    status: OrderStatus;
    revisions_used: number;
    delivery_message: string | null;
    delivery_url: string | null;
    paid_at: string | null;
    started_at: string | null;
    delivered_at: string | null;
    completed_at: string | null;
    transaction?: {
        id: number;
        title: string;
        service?: {
            id: number;
            title: string;
            revisions: number;
        } | null;
        buyer?: { id: number; name: string };
        seller?: { id: number; name: string };
    };
    events?: OrderEvent[];
    dispute?: import('./admin').DealDispute | null;
};
