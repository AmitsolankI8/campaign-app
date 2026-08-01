<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { create } from '@/actions/App/Http/Controllers/UserManagement/RoleController';
import type { PermissionGroups } from '../types';
import RoleForm from './Form.vue';

defineProps<{
    permissionGroups: PermissionGroups;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Create role',
                href: create(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Create role" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Create role</h1>
            <p class="text-sm text-muted-foreground">
                Add a role and assign static seeded permissions.
            </p>
        </div>

        <RoleForm :permission-groups="permissionGroups" />
    </div>
</template>
