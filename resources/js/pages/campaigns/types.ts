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

export const CONTACT_IMPORT_STATUS_KEY = {
    pending: 'pending',
    synced: 'synced',
} as const;

export type ContactImportStatus = {
    value: number;
    key: (typeof CONTACT_IMPORT_STATUS_KEY)[keyof typeof CONTACT_IMPORT_STATUS_KEY];
    label: string;
};

export type OnceOffCampaignContact = {
    id: string;
    first_name: string;
    last_name: string | null;
    number: string;
    email: string | null;
    created_at: string | null;
};

export type OnceOffCampaignContactImport = {
    id: string;
    file_name: string;
    contact_count: number;
    status: ContactImportStatus;
    created_at: string | null;
    synced_at: string | null;
};
