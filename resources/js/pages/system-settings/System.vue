<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    edit,
    update,
} from '@/actions/App/Http/Controllers/Settings/SystemSettingsController';
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
import { usePermissions } from '@/composables/usePermissions';

const props = defineProps<{ settings: { display_name: string } }>();
defineOptions({
    layout: { breadcrumbs: [{ title: 'System settings', href: edit() }] },
});
const { hasPermissions } = usePermissions();
const form = useForm({ display_name: props.settings.display_name });

const submit = () => {
    if (
        !hasPermissions(['system-settings.view', 'system-settings.edit'], true)
    ) {
        return;
    }

    form.put(update.url(), { preserveScroll: true });
};
</script>

<template>
    <Head title="System settings" />
    <div class="flex flex-1 flex-col gap-6 p-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">
                System settings
            </h1>
            <p class="text-sm text-muted-foreground">
                Manage settings that apply across the application.
            </p>
        </div>
        <Card class="max-w-3xl">
            <CardHeader>
                <CardTitle>General</CardTitle>
                <CardDescription
                    >Customize the name shown in the application
                    sidebar.</CardDescription
                >
            </CardHeader>
            <CardContent>
                <form
                    v-if="
                        hasPermissions(
                            ['system-settings.view', 'system-settings.edit'],
                            true,
                        )
                    "
                    class="space-y-6"
                    @submit.prevent="submit"
                >
                    <div class="grid gap-2">
                        <Label for="display_name" required
                            >System display name</Label
                        >
                        <Input
                            id="display_name"
                            v-model="form.display_name"
                            aria-required="true"
                            maxlength="255"
                            :aria-invalid="Boolean(form.errors.display_name)"
                        />
                        <InputError :message="form.errors.display_name" />
                    </div>
                    <Button type="submit" :disabled="form.processing">
                        <Spinner v-if="form.processing" />
                        Save settings
                    </Button>
                </form>
                <dl
                    v-else-if="hasPermissions(['system-settings.view'])"
                    class="grid gap-2"
                >
                    <dt class="text-sm text-muted-foreground">
                        System display name
                    </dt>
                    <dd class="text-sm font-medium">
                        {{ settings.display_name }}
                    </dd>
                </dl>
            </CardContent>
        </Card>
    </div>
</template>
