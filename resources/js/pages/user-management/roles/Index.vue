<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from '@lucide/vue';
import {
    create,
    destroy,
    edit,
    index,
} from '@/actions/App/Http/Controllers/UserManagement/RoleController';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/composables/usePermissions';

type RoleRow = {
    id: number;
    name: string;
    display_name: string;
    short_note: string | null;
    users_count: number;
};

defineProps<{
    roles: RoleRow[];
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

const deleteRole = (role: RoleRow) => {
    if (role.name === 'admin' || !hasPermissions(['roles.delete'])) {
        return;
    }

    if (!window.confirm(`Delete ${role.display_name}?`)) {
        return;
    }

    router.delete(destroy.url(role.id), { preserveScroll: true });
};
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

        <div class="overflow-hidden rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="px-4 py-3 font-medium">Role</th>
                        <th class="px-4 py-3 font-medium">Short note</th>
                        <th class="px-4 py-3 font-medium">Users</th>
                        <th
                            v-if="
                                hasPermissions(['roles.edit', 'roles.delete'])
                            "
                            class="w-40 px-4 py-3 text-right font-medium"
                        >
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="roles.length === 0">
                        <td
                            :colspan="
                                hasPermissions(['roles.edit', 'roles.delete'])
                                    ? 4
                                    : 3
                            "
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No roles found.
                        </td>
                    </tr>
                    <tr v-for="role in roles" :key="role.id" class="border-t">
                        <td class="px-4 py-3">
                            <div class="font-medium">
                                {{ role.display_name }}
                            </div>
                            <div class="text-xs text-muted-foreground">
                                {{ role.name }}
                            </div>
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ role.short_note ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ role.users_count }}
                        </td>
                        <td
                            v-if="
                                hasPermissions(['roles.edit', 'roles.delete'])
                            "
                            class="px-4 py-3"
                        >
                            <div
                                v-if="role.name !== 'admin'"
                                class="flex justify-end gap-2"
                            >
                                <Button
                                    v-if="hasPermissions(['roles.edit'])"
                                    size="sm"
                                    variant="outline"
                                    as-child
                                >
                                    <Link :href="edit.url(role.id)">
                                        <Pencil class="size-4" />
                                        Edit
                                    </Link>
                                </Button>
                                <Button
                                    v-if="hasPermissions(['roles.delete'])"
                                    size="sm"
                                    variant="destructive"
                                    type="button"
                                    @click="deleteRole(role)"
                                >
                                    <Trash2 class="size-4" />
                                    Delete
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
