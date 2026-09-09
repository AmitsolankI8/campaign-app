<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { index } from '@/actions/App/Http/Controllers/UserManagement/UserController';
import type {
    PreferenceOptions,
    UserPreferenceValues,
} from '@/types/preferences';
import type { RoleOption } from '../types';
import UserForm from './Form.vue';

type ManagedUser = {
    id: string;
    first_name: string;
    last_name: string;
    email: string;
    roles: string[];
    roles_locked: boolean;
    can_edit: boolean;
    preferences: UserPreferenceValues;
};

defineProps<{
    managedUser: ManagedUser;
    roles: RoleOption[];
    preferenceOptions: PreferenceOptions;
    defaultPreferences: UserPreferenceValues;
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
                Update the user profile and role assignment.
            </p>
        </div>

        <UserForm
            :managed-user="managedUser"
            :roles="roles"
            :preference-options="preferenceOptions"
            :default-preferences="defaultPreferences"
        />
    </div>
</template>
