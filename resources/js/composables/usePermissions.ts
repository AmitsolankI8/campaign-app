import { usePage } from '@inertiajs/vue3';

export function usePermissions() {
    const page = usePage();

    const hasPermissions = (
        permissions: string[],
        checkAll = false,
    ): boolean => {
        const allowedPermissions = page.props.auth?.permissions ?? [];

        if (
            page.props.auth?.user?.roles?.some((role) => role.name === 'admin')
        ) {
            return true;
        }

        if (permissions.length === 0) {
            return false;
        }

        for (const permission of permissions) {
            const allowed = allowedPermissions.includes(permission);

            if (!checkAll && allowed) {
                return true;
            }

            if (checkAll && !allowed) {
                return false;
            }
        }

        return checkAll;
    };

    return { hasPermissions };
}
