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
