<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import {
    create,
    destroy,
    edit,
    index,
} from '@/actions/App/Http/Controllers/UserManagement/UserController';
import DataTable from '@/components/data-table/DataTable.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { usePermissions } from '@/composables/usePermissions';
import type { DataTableColumn, DataTableData } from '@/types/data-table';

type UserRow = {
    id: string;
    full_name: string;
    email: string;
    created_at: string | null;
    roles: string[];
    can_delete: boolean;
    can_edit: boolean;
};

const props = defineProps<{
    users: DataTableData<UserRow>;
    roleOptions: { name: string; display_name: string }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Users',
                href: index(),
            },
        ],
    },
});

const { hasPermissions } = usePermissions();
const page = usePage();

const canEditUser = (user: UserRow): boolean =>
    hasPermissions(['users.edit']) &&
    (user.id === page.props.auth?.user?.id || user.can_edit === true);

const showActions = computed(
    () =>
        props.users.data.some(canEditUser) ||
        (hasPermissions(['users.delete']) &&
            props.users.data.some((user) => user.can_delete)),
);

const deleteUser = (user: UserRow) => {
    if (!hasPermissions(['users.delete']) || !user.can_delete) {
        return;
    }

    if (!window.confirm(`Delete ${user.full_name}?`)) {
        return;
    }

    router.delete(destroy.url(user.id), { preserveScroll: true });
};
const columns = computed<DataTableColumn<UserRow>[]>(() => [
    { key: 'full_name', label: 'Name', cellClass: 'font-medium' },
    { key: 'email', label: 'Email', cellClass: 'text-muted-foreground' },
    { key: 'roles', label: 'Roles' },
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
    <Head title="Users" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Users</h1>
                <p class="text-sm text-muted-foreground">
                    Create users and assign their roles.
                </p>
            </div>
            <Button v-if="hasPermissions(['users.create'])" as-child>
                <Link :href="create.url()">
                    <Plus class="size-4" />
                    Create user
                </Link>
            </Button>
        </div>

        <DataTable
            :data="users"
            :columns="columns"
            prop-name="users"
            caption="Users"
            empty-message="No users found."
        >
            <template #extra-filters="{ filters, loading }">
                <Select
                    :model-value="String(filters.role ?? '__all')"
                    :disabled="loading"
                    @update:model-value="
                        filters.role =
                            $event === '__all' ? null : String($event)
                    "
                >
                    <SelectTrigger class="w-48" aria-label="Filter by role">
                        <SelectValue placeholder="All roles" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="__all">All roles</SelectItem>
                        <SelectItem
                            v-for="role in roleOptions"
                            :key="role.name"
                            :value="role.name"
                        >
                            {{ role.display_name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </template>
            <template #cell-roles="{ row: user }">
                <div class="flex flex-wrap gap-2">
                    <Badge
                        v-for="role in user.roles"
                        :key="role"
                        variant="secondary"
                        >{{ role }}</Badge
                    >
                    <span
                        v-if="!user.roles.length"
                        class="text-muted-foreground"
                        >No roles</span
                    >
                </div>
            </template>
            <template #cell-actions="{ row: user }">
                <div class="flex justify-end gap-2">
                    <Button
                        v-if="canEditUser(user)"
                        size="sm"
                        variant="outline"
                        as-child
                    >
                        <Link :href="edit.url(user.id)"
                            ><Pencil class="size-4" />Edit</Link
                        >
                    </Button>
                    <Button
                        v-if="
                            hasPermissions(['users.delete']) && user.can_delete
                        "
                        size="sm"
                        variant="destructive"
                        type="button"
                        @click="deleteUser(user)"
                        ><Trash2 class="size-4" />Delete</Button
                    >
                </div>
            </template>
        </DataTable>
    </div>
</template>
