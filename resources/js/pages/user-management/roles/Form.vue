<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import {
    index as rolesIndex,
    store,
    update,
} from '@/actions/App/Http/Controllers/UserManagement/RoleController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import PermissionCheckboxTable from '../components/PermissionCheckboxTable.vue';
import type { PermissionGroups } from '../types';

type ManagedRole = {
    id: number;
    name: string;
    display_name: string;
    short_note: string | null;
    permissions: string[];
};

const props = defineProps<{
    role?: ManagedRole;
    permissionGroups: PermissionGroups;
}>();

const isEditing = Boolean(props.role);

const form = useForm({
    display_name: props.role?.display_name ?? '',
    name: props.role?.name ?? '',
    short_note: props.role?.short_note ?? '',
    permissions: props.role?.permissions ?? [],
});

const togglePermission = (permission: string, checked: boolean) => {
    form.permissions = checked
        ? [...form.permissions, permission]
        : form.permissions.filter((item) => item !== permission);
};

const submit = () => {
    if (props.role) {
        form.put(update.url(props.role.id), { preserveScroll: true });

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
                    isEditing ? 'Edit role' : 'Create role'
                }}</CardTitle>
                <CardDescription>
                    Roles are created here; permissions come from static
                    seeders.
                </CardDescription>
            </CardHeader>
            <CardContent class="grid gap-6 md:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="display_name">Display name</Label>
                    <Input
                        id="display_name"
                        v-model="form.display_name"
                        required
                    />
                    <InputError :message="form.errors.display_name" />
                </div>

                <div class="grid gap-2">
                    <Label for="name">Slug</Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        placeholder="support-manager"
                        required
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2 md:col-span-2">
                    <Label for="short_note">Short note</Label>
                    <Input id="short_note" v-model="form.short_note" />
                    <InputError :message="form.errors.short_note" />
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Permissions</CardTitle>
                <CardDescription>
                    Check permissions and update the role assignment.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <PermissionCheckboxTable
                    :permission-groups="permissionGroups"
                    :selected="form.permissions"
                    @toggle="togglePermission"
                />
                <InputError :message="form.errors.permissions" class="mt-2" />
            </CardContent>
        </Card>

        <div class="flex items-center justify-end gap-3">
            <Button type="button" variant="outline" as-child>
                <Link :href="rolesIndex.url()">Cancel</Link>
            </Button>
            <Button type="submit" :disabled="form.processing">
                <Spinner v-if="form.processing" />
                {{ isEditing ? 'Update role' : 'Create role' }}
            </Button>
        </div>
    </form>
</template>
