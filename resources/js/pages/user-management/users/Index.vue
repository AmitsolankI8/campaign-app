<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from '@lucide/vue';
import {
    create,
    destroy,
    edit,
    index,
} from '@/actions/App/Http/Controllers/UserManagement/UserController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/composables/usePermissions';

type UserRow = {
    id: number;
    full_name: string;
    email: string;
    created_at: string | null;
    roles: string[];
};

defineProps<{
    users: UserRow[];
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

const deleteUser = (user: UserRow) => {
    if (!hasPermissions(['users.delete'])) {
        return;
    }

    if (!window.confirm(`Delete ${user.full_name}?`)) {
        return;
    }

    router.delete(destroy.url(user.id), { preserveScroll: true });
};
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

        <div class="overflow-hidden rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">Email</th>
                        <th class="px-4 py-3 font-medium">Roles</th>
                        <th class="px-4 py-3 font-medium">Created</th>
                        <th
                            v-if="
                                hasPermissions(['users.edit', 'users.delete'])
                            "
                            class="w-40 px-4 py-3 text-right font-medium"
                        >
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="users.length === 0">
                        <td
                            :colspan="
                                hasPermissions(['users.edit', 'users.delete'])
                                    ? 5
                                    : 4
                            "
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No users found.
                        </td>
                    </tr>
                    <tr v-for="user in users" :key="user.id" class="border-t">
                        <td class="px-4 py-3 font-medium">
                            {{ user.full_name }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ user.email }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-2">
                                <Badge
                                    v-for="role in user.roles"
                                    :key="role"
                                    variant="secondary"
                                >
                                    {{ role }}
                                </Badge>
                                <span
                                    v-if="user.roles.length === 0"
                                    class="text-muted-foreground"
                                >
                                    No roles
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ user.created_at ?? '—' }}
                        </td>
                        <td
                            v-if="
                                hasPermissions(['users.edit', 'users.delete'])
                            "
                            class="px-4 py-3"
                        >
                            <div class="flex justify-end gap-2">
                                <Button
                                    v-if="hasPermissions(['users.edit'])"
                                    size="sm"
                                    variant="outline"
                                    as-child
                                >
                                    <Link :href="edit.url(user.id)">
                                        <Pencil class="size-4" />
                                        Edit
                                    </Link>
                                </Button>
                                <Button
                                    v-if="hasPermissions(['users.delete'])"
                                    size="sm"
                                    variant="destructive"
                                    type="button"
                                    @click="deleteUser(user)"
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
