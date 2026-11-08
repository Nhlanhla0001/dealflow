export type DisputeStatus = 'open' | 'under_review' | 'resolved' | 'rejected';

export type DisputeEvent = {
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

export type DealDispute = {
    id: number;
    order_id: number;
    opened_by: number;
    reason: string;
    description: string;
    evidence_url: string | null;
    evidence_path: string | null;
    evidence_original_name: string | null;
    status: DisputeStatus;
    resolution: string | null;
    resolved_by: number | null;
    resolved_at: string | null;
    created_at: string;
    opener?: {
        id: number;
        name: string;
    };
    resolver?: {
        id: number;
        name: string;
    } | null;
    order?: {
        id: number;
        transaction?: {
            id: number;
            title: string;
            buyer?: { id: number; name: string };
            seller?: { id: number; name: string };
        };
    };
    events?: DisputeEvent[];
};
