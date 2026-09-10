export const CAMPAIGN_TYPE_KEY = {
    onceOff: 'once_off',
    ongoing: 'ongoing',
    batchProcessing: 'batch_processing',
} as const;

export type CampaignTypeKey =
    (typeof CAMPAIGN_TYPE_KEY)[keyof typeof CAMPAIGN_TYPE_KEY];

export type CampaignTypePayload = {
    value: number;
    key: CampaignTypeKey;
    label: string;
};

export type CampaignTypeOption = {
    value: number;
    key: CampaignTypeKey;
    label: string;
};

export const CAMPAIGN_STATUS_KEY = {
    draft: 'draft',
    launched: 'launched',
    running: 'running',
    paused: 'paused',
    cancelled: 'cancelled',
} as const;

export type CampaignStatusKey =
    (typeof CAMPAIGN_STATUS_KEY)[keyof typeof CAMPAIGN_STATUS_KEY];

export type CampaignStatusPayload = {
    value: number;
    key: CampaignStatusKey;
    label: string;
};

export type CampaignStatusOption = {
    value: number;
    key: CampaignStatusKey;
    label: string;
};

export type Campaign = {
    id: string;
    name: string;
    short_note: string | null;
    type: CampaignTypePayload;
    status: CampaignStatusPayload;
    show_url: string;
    created_at: string | null;
    updated_at: string | null;
};

export type CampaignRow = Omit<Campaign, 'updated_at'>;
