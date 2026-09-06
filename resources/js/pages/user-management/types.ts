export type PermissionItem = {
    name: string;
    display_name: string;
    short_note: string;
};

export type PermissionGroups = Record<string, PermissionItem[]>;

export type RoleOption = {
    name: string;
    display_name: string;
};
