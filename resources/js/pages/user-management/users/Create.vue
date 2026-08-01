<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { create } from '@/actions/App/Http/Controllers/UserManagement/UserController';
import type { PermissionGroups, RoleOption } from '../types';
import UserForm from './Form.vue';

defineProps<{
    roles: RoleOption[];
    permissionGroups: PermissionGroups;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Create user',
                href: create(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Create user" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Create user</h1>
            <p class="text-sm text-muted-foreground">
                Add a user without enabling public self-registration.
            </p>
        </div>

        <UserForm :roles="roles" :permission-groups="permissionGroups" />
    </div>
</template>
