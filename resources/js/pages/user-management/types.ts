export type PermissionItem = {
    name: string;
    label: string;
};

export type PermissionGroups = Record<string, PermissionItem[]>;

export type RoleOption = {
    name: string;
};
