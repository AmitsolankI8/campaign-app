<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import {
    create,
    destroy,
    edit,
    index,
} from '@/actions/App/Http/Controllers/UserManagement/RoleController';
import DataTable from '@/components/data-table/DataTable.vue';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/composables/usePermissions';
import type { DataTableColumn, DataTableData } from '@/types/data-table';

type RoleRow = {
    id: string;
    name: string;
    display_name: string;
    short_note: string | null;
    users_count: number;
    created_at: string | null;
};

const props = defineProps<{
    roles: DataTableData<RoleRow>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Roles',
                href: index(),
            },
        ],
    },
});

const { hasPermissions } = usePermissions();

const canEditRole = (role: RoleRow): boolean =>
    role.name !== 'admin' && hasPermissions(['roles.edit']);
const canDeleteRole = (role: RoleRow): boolean =>
    role.name !== 'admin' &&
    role.users_count === 0 &&
    hasPermissions(['roles.delete']);
const showActions = computed(() =>
    props.roles.data.some((role) => canEditRole(role) || canDeleteRole(role)),
);

const deleteRole = (role: RoleRow) => {
    if (!canDeleteRole(role)) {
        return;
    }

    if (!window.confirm(`Delete ${role.display_name}?`)) {
        return;
    }

    router.delete(destroy.url(role.id), { preserveScroll: true });
};
const columns = computed<DataTableColumn<RoleRow>[]>(() => [
    { key: 'display_name', label: 'Role' },
    {
        key: 'short_note',
        label: 'Short note',
        cellClass: 'text-muted-foreground',
    },
    { key: 'users_count', label: 'Users', cellClass: 'text-muted-foreground' },
    {
        key: 'created_at',
        label: 'Created At',
        format: 'datetime',
        cellClass: 'text-muted-foreground',
    },
    ...(showActions.value
        ? [{ key: 'actions', label: 'Actions', headerClass: 'w-40 text-right' }]
        : []),
]);
</script>

<template>
    <Head title="Roles" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Roles</h1>
                <p class="text-sm text-muted-foreground">
                    Create roles and assign seeded permissions.
                </p>
            </div>
            <Button v-if="hasPermissions(['roles.create'])" as-child>
                <Link :href="create.url()">
                    <Plus class="size-4" />
                    Create role
                </Link>
            </Button>
        </div>

        <DataTable
            :data="roles"
            :columns="columns"
            prop-name="roles"
            caption="Roles"
            empty-message="No roles found."
        >
            <template #cell-display_name="{ row: role }">
                <div class="font-medium">{{ role.display_name }}</div>
                <div class="text-xs text-muted-foreground">{{ role.name }}</div>
            </template>
            <template #cell-actions="{ row: role }">
                <div class="flex justify-end gap-2">
                    <Button
                        v-if="canEditRole(role)"
                        size="sm"
                        variant="outline"
                        as-child
                    >
                        <Link :href="edit.url(role.id)"
                            ><Pencil class="size-4" />Edit</Link
                        >
                    </Button>
                    <Button
                        v-if="canDeleteRole(role)"
                        size="sm"
                        variant="destructive"
                        type="button"
                        @click="deleteRole(role)"
                        ><Trash2 class="size-4" />Delete</Button
                    >
                </div>
            </template>
        </DataTable>
    </div>
</template>
