<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import {
    index as usersIndex,
    store,
    update,
} from '@/actions/App/Http/Controllers/UserManagement/UserController';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
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
import PermissionCheckboxTable from '../components/PermissionCheckboxTable.vue';
import type { PermissionGroups, RoleOption } from '../types';

type ManagedUser = {
    id: number;
    name: string;
    email: string;
    roles: string[];
    permissions: string[];
};

const props = defineProps<{
    managedUser?: ManagedUser;
    roles: RoleOption[];
    permissionGroups: PermissionGroups;
}>();

const isEditing = Boolean(props.managedUser);

const form = useForm({
    name: props.managedUser?.name ?? '',
    email: props.managedUser?.email ?? '',
    password: '',
    password_confirmation: '',
    roles: props.managedUser?.roles ?? [],
    permissions: props.managedUser?.permissions ?? [],
});

const toggleValue = (
    field: 'roles' | 'permissions',
    value: string,
    checked: boolean,
) => {
    form[field] = checked
        ? [...form[field], value]
        : form[field].filter((item) => item !== value);
};

const submit = () => {
    if (props.managedUser) {
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
    <form class="space-y-6" @submit.prevent="submit">
        <Card>
            <CardHeader>
                <CardTitle>{{
                    isEditing ? 'Edit user' : 'Create user'
                }}</CardTitle>
                <CardDescription>
                    Manage the user profile, role assignment, and direct
                    permissions.
                </CardDescription>
            </CardHeader>
            <CardContent class="grid gap-6 md:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input id="name" v-model="form.name" required />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Email</Label>
                    <Input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">
                        Password
                        <span v-if="isEditing" class="text-muted-foreground">
                            (leave blank to keep current)
                        </span>
                    </Label>
                    <PasswordInput
                        id="password"
                        v-model="form.password"
                        :required="!isEditing"
                        autocomplete="new-password"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirm password</Label>
                    <PasswordInput
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        :required="!isEditing"
                        autocomplete="new-password"
                    />
                    <InputError :message="form.errors.password_confirmation" />
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Role assignment</CardTitle>
                <CardDescription>
                    Assign one or more seeded roles to this user.
                </CardDescription>
            </CardHeader>
            <CardContent class="grid gap-3 md:grid-cols-2">
                <Label
                    v-for="role in roles"
                    :key="role.name"
                    class="flex items-center gap-3 rounded-lg border p-3"
                >
                    <Checkbox
                        :model-value="form.roles.includes(role.name)"
                        @update:model-value="
                            (value) =>
                                toggleValue('roles', role.name, value === true)
                        "
                    />
                    <span>{{ role.name }}</span>
                </Label>
                <InputError
                    :message="form.errors.roles"
                    class="md:col-span-2"
                />
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Direct permissions</CardTitle>
                <CardDescription>
                    Optional user-specific permissions from the static seeded
                    permission list.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <PermissionCheckboxTable
                    :permission-groups="permissionGroups"
                    :selected="form.permissions"
                    @toggle="
                        (permission, checked) =>
                            toggleValue('permissions', permission, checked)
                    "
                />
                <InputError :message="form.errors.permissions" class="mt-2" />
            </CardContent>
        </Card>

        <div class="flex items-center justify-end gap-3">
            <Button type="button" variant="outline" as-child>
                <a :href="usersIndex.url()">Cancel</a>
            </Button>
            <Button type="submit" :disabled="form.processing">
                <Spinner v-if="form.processing" />
                {{ isEditing ? 'Update user' : 'Create user' }}
            </Button>
        </div>
    </form>
</template>
