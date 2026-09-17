import type { CommunicationWorkStatus } from '@/types/communication';

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
    completed: 'completed',
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
    scheduled_at: string | null;
    created_at: string | null;
    updated_at: string | null;
};

export type CampaignRow = Omit<Campaign, 'updated_at' | 'scheduled_at'>;

export const CONTACT_IMPORT_STATUS_KEY = {
    pending: 'pending',
    synced: 'synced',
    partiallySynced: 'partially_synced',
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
    source: ContactUploadSource;
    mode: ContactUploadMode;
    uploaded_by: string | null;
    processed_count: number;
    history_count: number;
    removed_count: number;
    download_url: string | null;
};

export const CONTACT_UPLOAD_MODE_KEY = {
    append: 'append',
    update: 'update',
    replace: 'replace',
} as const;
export const CONTACT_UPLOAD_SOURCE_KEY = {
    manual: 'manual',
    file: 'file',
} as const;
export const CONTACT_UPLOAD_ROW_STATUS_KEY = {
    pending: 'pending',
    added: 'added',
    updated: 'updated',
    skipped: 'skipped',
    failed: 'failed',
} as const;
type Option<K extends string> = { value: number; key: K; label: string };
export type ContactUploadMode = Option<
    (typeof CONTACT_UPLOAD_MODE_KEY)[keyof typeof CONTACT_UPLOAD_MODE_KEY]
>;
export type ContactUploadSource = Option<
    (typeof CONTACT_UPLOAD_SOURCE_KEY)[keyof typeof CONTACT_UPLOAD_SOURCE_KEY]
>;
export type ContactUploadRowStatus = Option<
    (typeof CONTACT_UPLOAD_ROW_STATUS_KEY)[keyof typeof CONTACT_UPLOAD_ROW_STATUS_KEY]
>;
export type ContactUploadRow = Omit<OnceOffCampaignContact, 'created_at'> & {
    row_number: number;
    status: ContactUploadRowStatus;
    planned_action: 'add' | 'update' | 'skip' | null;
    error: string | null;
    before_values: Record<string, string | null> | null;
    synced_at: string | null;
};
export type ContactFilePreview = {
    headers: string[];
    columns: string[];
    rows: {
        row_number: number;
        values: string[];
        errors: Record<string, string[]>;
    }[];
    errors: string[];
    error_count: number;
};
export type ContactSyncPlan = {
    add: number;
    update: number;
    skip: number;
    remove: number;
    fingerprint: string;
};

export type OnceOffCampaignSchedule = {
    id: string;
    attempt_number: number;
    scheduled_at: string;
    channel: string;
    timezone: string;
    status: CommunicationWorkStatus;
};

export type CampaignScheduleChannel = {
    value: string;
    label: string;
};
