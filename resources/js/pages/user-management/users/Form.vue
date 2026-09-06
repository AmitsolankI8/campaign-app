<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
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
import type { RoleOption } from '../types';

type ManagedUser = {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    roles: string[];
};

const props = defineProps<{
    managedUser?: ManagedUser;
    roles: RoleOption[];
}>();

const isEditing = Boolean(props.managedUser);

const form = useForm({
    first_name: props.managedUser?.first_name ?? '',
    last_name: props.managedUser?.last_name ?? '',
    email: props.managedUser?.email ?? '',
    password: '',
    password_confirmation: '',
    roles: props.managedUser?.roles ?? [],
});

const toggleRole = (role: string, checked: boolean) => {
    form.roles = checked
        ? [...form.roles, role]
        : form.roles.filter((item) => item !== role);
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
                    Manage the user profile and role assignment.
                </CardDescription>
            </CardHeader>
            <CardContent class="grid gap-6 md:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="first_name">First name</Label>
                    <Input
                        id="first_name"
                        v-model="form.first_name"
                        required
                        autocomplete="given-name"
                    />
                    <InputError :message="form.errors.first_name" />
                </div>

                <div class="grid gap-2">
                    <Label for="last_name">Last name</Label>
                    <Input
                        id="last_name"
                        v-model="form.last_name"
                        required
                        autocomplete="family-name"
                    />
                    <InputError :message="form.errors.last_name" />
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
                <Link :href="usersIndex.url()">Cancel</Link>
            </Button>
            <Button type="submit" :disabled="form.processing">
                <Spinner v-if="form.processing" />
                {{ isEditing ? 'Update user' : 'Create user' }}
            </Button>
        </div>
    </form>
</template>
