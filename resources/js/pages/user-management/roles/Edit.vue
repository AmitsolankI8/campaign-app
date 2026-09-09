<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { index } from '@/actions/App/Http/Controllers/UserManagement/RoleController';
import type { PermissionGroups } from '../types';
import RoleForm from './Form.vue';

type ManagedRole = {
    id: string;
    name: string;
    display_name: string;
    short_note: string | null;
    permissions: string[];
};

defineProps<{
    role: ManagedRole;
    permissionGroups: PermissionGroups;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Edit role',
                href: index(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Edit role" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Edit role</h1>
            <p class="text-sm text-muted-foreground">
                Update role details and permission assignment.
            </p>
        </div>

        <RoleForm :role="role" :permission-groups="permissionGroups" />
    </div>
</template>
