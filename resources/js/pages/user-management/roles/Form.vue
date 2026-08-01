<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
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
    permissions: string[];
};

const props = defineProps<{
    role?: ManagedRole;
    permissionGroups: PermissionGroups;
}>();

const isEditing = Boolean(props.role);

const form = useForm({
    name: props.role?.name ?? '',
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
            <CardContent class="grid gap-2">
                <Label for="name">Name</Label>
                <Input id="name" v-model="form.name" required />
                <InputError :message="form.errors.name" />
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
                <a :href="rolesIndex.url()">Cancel</a>
            </Button>
            <Button type="submit" :disabled="form.processing">
                <Spinner v-if="form.processing" />
                {{ isEditing ? 'Update role' : 'Create role' }}
            </Button>
        </div>
    </form>
</template>
