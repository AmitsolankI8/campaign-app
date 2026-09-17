export const COMMUNICATION_WORK_STATUS_KEY = {
    pending: 'pending',
    processing: 'processing',
    completed: 'completed',
    cancelled: 'cancelled',
    expired: 'expired',
    failed: 'failed',
} as const;

export type CommunicationWorkStatus = {
    value: number;
    key: (typeof COMMUNICATION_WORK_STATUS_KEY)[keyof typeof COMMUNICATION_WORK_STATUS_KEY];
    label: string;
};

export type CommunicationProvider = {
    id: string | null;
    provider: string;
    name: string;
    is_active: boolean;
    priority: number;
    fields: {
        key: string;
        label: string;
        type: string;
        secret: boolean;
        required: boolean;
        options?: { value: string; label: string }[];
    }[];
    credentials: Record<string, string>;
    saved_secrets: string[];
};
