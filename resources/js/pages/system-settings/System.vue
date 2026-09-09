<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    edit,
    update,
} from '@/actions/App/Http/Controllers/Settings/SystemSettingsController';
import InputError from '@/components/InputError.vue';
import PreferenceSelectFields from '@/components/PreferenceSelectFields.vue';
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
import type { PreferenceOptions } from '@/types/preferences';

type SystemSettings = {
    display_name: string;
    default_country_preference_id: number;
    default_timezone_preference_id: number;
    default_language_preference_id: number;
    default_number_format_preference_id: number;
    default_date_format_preference_id: number;
    default_time_format_preference_id: number;
};

const props = defineProps<{
    settings: SystemSettings;
    preferenceOptions: PreferenceOptions;
}>();
defineOptions({
    layout: { breadcrumbs: [{ title: 'System settings', href: edit() }] },
});
const { hasPermissions } = usePermissions();
const form = useForm({
    display_name: props.settings.display_name,
    default_country_preference_id: String(
        props.settings.default_country_preference_id,
    ),
    default_timezone_preference_id: String(
        props.settings.default_timezone_preference_id,
    ),
    default_language_preference_id: String(
        props.settings.default_language_preference_id,
    ),
    default_number_format_preference_id: String(
        props.settings.default_number_format_preference_id,
    ),
    default_date_format_preference_id: String(
        props.settings.default_date_format_preference_id,
    ),
    default_time_format_preference_id: String(
        props.settings.default_time_format_preference_id,
    ),
});

const labelFor = <T extends { id: number; display_name: string }>(
    options: T[],
    id: number,
): string => options.find((option) => option.id === id)?.display_name ?? '';

const updatePreferenceField = (field: string, value: string | number): void => {
    (form as unknown as Record<string, string | number>)[field] = value;
};

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

                    <CardTitle class="text-base">Default preferences</CardTitle>
                    <PreferenceSelectFields
                        :form="form"
                        :errors="form.errors"
                        :options="preferenceOptions"
                        field-prefix="default_"
                        @update-field="updatePreferenceField"
                    />

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
                    <dt class="pt-4 text-sm text-muted-foreground">
                        Default country
                    </dt>
                    <dd class="text-sm font-medium">
                        {{
                            labelFor(
                                preferenceOptions.countries,
                                settings.default_country_preference_id,
                            )
                        }}
                    </dd>
                    <dt class="text-sm text-muted-foreground">
                        Default timezone
                    </dt>
                    <dd class="text-sm font-medium">
                        {{
                            labelFor(
                                preferenceOptions.timezones,
                                settings.default_timezone_preference_id,
                            )
                        }}
                    </dd>
                    <dt class="text-sm text-muted-foreground">
                        Default language
                    </dt>
                    <dd class="text-sm font-medium">
                        {{
                            labelFor(
                                preferenceOptions.languages,
                                settings.default_language_preference_id,
                            )
                        }}
                    </dd>
                    <dt class="text-sm text-muted-foreground">
                        Default number format
                    </dt>
                    <dd class="text-sm font-medium">
                        {{
                            labelFor(
                                preferenceOptions.numberFormats,
                                settings.default_number_format_preference_id,
                            )
                        }}
                    </dd>
                    <dt class="text-sm text-muted-foreground">
                        Default date format
                    </dt>
                    <dd class="text-sm font-medium">
                        {{
                            labelFor(
                                preferenceOptions.dateFormats,
                                settings.default_date_format_preference_id,
                            )
                        }}
                    </dd>
                    <dt class="text-sm text-muted-foreground">
                        Default time format
                    </dt>
                    <dd class="text-sm font-medium">
                        {{
                            labelFor(
                                preferenceOptions.timeFormats,
                                settings.default_time_format_preference_id,
                            )
                        }}
                    </dd>
                </dl>
            </CardContent>
        </Card>
    </div>
</template>
