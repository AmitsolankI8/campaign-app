import type { UserPreferences } from '@/types/preferences';

export type Role = {
    id: string;
    name: string;
    display_name: string | null;
    short_note: string | null;
    guard_name: string;
};

export type User = {
    id: string;
    first_name: string;
    last_name: string;
    full_name: string;
    email: string;
    roles: Role[];
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
    preferences: UserPreferences | null;
    permissions: string[];
};

/* @chisel-passkeys */
export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};
/* @end-chisel-passkeys */

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
