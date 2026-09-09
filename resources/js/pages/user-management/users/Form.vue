<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import {
    index as usersIndex,
    store,
    update,
} from '@/actions/App/Http/Controllers/UserManagement/UserController';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import PreferenceSelectFields from '@/components/PreferenceSelectFields.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { usePermissions } from '@/composables/usePermissions';
import { dashboard } from '@/routes';
import type {
    PreferenceOptions,
    UserPreferenceValues,
} from '@/types/preferences';
import type { RoleOption } from '../types';

type ManagedUser = {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    roles: string[];
    roles_locked: boolean;
    can_edit: boolean;
    preferences: UserPreferenceValues;
};

const props = defineProps<{
    managedUser?: ManagedUser;
    roles: RoleOption[];
    preferenceOptions: PreferenceOptions;
    defaultPreferences: UserPreferenceValues;
}>();

const { hasPermissions } = usePermissions();

const isEditing = Boolean(props.managedUser);
const preferences = props.managedUser?.preferences ?? props.defaultPreferences;

const form = useForm({
    first_name: props.managedUser?.first_name ?? '',
    last_name: props.managedUser?.last_name ?? '',
    email: props.managedUser?.email ?? '',
    password: '',
    password_confirmation: '',
    roles: props.managedUser?.roles ?? [],
    country_preference_id: String(preferences.country_preference_id),
    timezone_preference_id: String(preferences.timezone_preference_id),
    language_preference_id: String(preferences.language_preference_id),
    number_format_preference_id: String(
        preferences.number_format_preference_id,
    ),
    date_format_preference_id: String(preferences.date_format_preference_id),
    time_format_preference_id: String(preferences.time_format_preference_id),
});

const toggleRole = (role: string, checked: boolean) => {
    if (props.managedUser?.roles_locked || role === 'admin') {
        return;
    }

    form.roles = checked
        ? [...form.roles, role]
        : form.roles.filter((item) => item !== role);
};

const updatePreferenceField = (field: string, value: string | number): void => {
    (form as unknown as Record<string, string | number>)[field] = value;
};

const submit = () => {
    if (
        !hasPermissions([isEditing ? 'users.edit' : 'users.create']) ||
        (props.managedUser && !props.managedUser.can_edit)
    ) {
        return;
    }

    if (props.managedUser) {
        if (props.managedUser.roles_locked) {
            form.roles = [...props.managedUser.roles];
        }

        form.put(update.url(props.managedUser.id), {
            preserveScroll: true,
            onSuccess: () => form.reset('password', 'password_confirmation'),
        });

        return;
    }

    form.post(store.url(), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <form novalidate class="space-y-6" @submit.prevent="submit">
        <Card>
            <CardHeader>
                <CardTitle>{{
                    isEditing ? 'Edit user' : 'Create user'
                }}</CardTitle>
                <CardDescription>
                    Manage the user profile and role assignment.
                </CardDescription>
            </CardHeader>
            <CardContent class="grid gap-6 md:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="first_name" required>First name</Label>
                    <Input
                        id="first_name"
                        aria-required="true"
                        v-model="form.first_name"
                        autocomplete="given-name"
                    />
                    <InputError :message="form.errors.first_name" />
                </div>

                <div class="grid gap-2">
                    <Label for="last_name" required>Last name</Label>
                    <Input
                        id="last_name"
                        aria-required="true"
                        v-model="form.last_name"
                        autocomplete="family-name"
                    />
                    <InputError :message="form.errors.last_name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email" required>Email</Label>
                    <Input
                        id="email"
                        aria-required="true"
                        v-model="form.email"
                        type="email"
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password" :required="!isEditing">
                        Password
                        <span v-if="isEditing" class="text-muted-foreground">
                            (leave blank to keep current)
                        </span>
                    </Label>
                    <PasswordInput
                        id="password"
                        :aria-required="!isEditing"
                        v-model="form.password"
                        autocomplete="new-password"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label
                        for="password_confirmation"
                        :required="!isEditing || Boolean(form.password)"
                        >Confirm password</Label
                    >
                    <PasswordInput
                        id="password_confirmation"
                        :aria-required="!isEditing || Boolean(form.password)"
                        v-model="form.password_confirmation"
                        autocomplete="new-password"
                    />
                    <InputError :message="form.errors.password_confirmation" />
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Preferences</CardTitle>
                <CardDescription>
                    Country, timezone, language, and formats.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <PreferenceSelectFields
                    :form="form"
                    :errors="form.errors"
                    :options="preferenceOptions"
                    @update-field="updatePreferenceField"
                />
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Role assignment</CardTitle>
                <CardDescription>
                    {{
                        managedUser?.roles_locked
                            ? 'The administrator user’s roles cannot be changed.'
                            : 'Assign roles to this user. The administrator role is reserved.'
                    }}
                </CardDescription>
            </CardHeader>
            <CardContent class="grid gap-3 md:grid-cols-2">
                <Label
                    v-for="role in roles"
                    :key="role.name"
                    class="flex items-center gap-3 rounded-lg border p-3"
                >
                    <Checkbox
                        :disabled="
                            managedUser?.roles_locked || role.name === 'admin'
                        "
                        :model-value="form.roles.includes(role.name)"
                        @update:model-value="
                            (value) => toggleRole(role.name, value === true)
                        "
                    />
                    <span>{{ role.display_name }}</span>
                </Label>
                <InputError
                    :message="form.errors.roles"
                    class="md:col-span-2"
                />
            </CardContent>
        </Card>

        <div class="flex items-center justify-end gap-3">
            <Button type="button" variant="outline" as-child>
                <Link
                    :href="
                        hasPermissions(['users.view'])
                            ? usersIndex.url()
                            : dashboard.url()
                    "
                    >Cancel</Link
                >
            </Button>
            <Button
                v-if="
                    hasPermissions([
                        isEditing ? 'users.edit' : 'users.create',
                    ]) &&
                    (!managedUser || managedUser.can_edit)
                "
                type="submit"
                :disabled="form.processing"
            >
                <Spinner v-if="form.processing" />
                {{ isEditing ? 'Update user' : 'Create user' }}
            </Button>
        </div>
    </form>
</template>
