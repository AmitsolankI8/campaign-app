<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { index } from '@/actions/App/Http/Controllers/UserManagement/UserController';
import type { PermissionGroups, RoleOption } from '../types';
import UserForm from './Form.vue';

type ManagedUser = {
    id: number;
    name: string;
    email: string;
    roles: string[];
    permissions: string[];
};

defineProps<{
    managedUser: ManagedUser;
    roles: RoleOption[];
    permissionGroups: PermissionGroups;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Edit user',
                href: index(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Edit user" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Edit user</h1>
            <p class="text-sm text-muted-foreground">
                Update profile, role assignment, and direct permissions.
            </p>
        </div>

        <UserForm
            :managed-user="managedUser"
            :roles="roles"
            :permission-groups="permissionGroups"
        />
    </div>
</template>
