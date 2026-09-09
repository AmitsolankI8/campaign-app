<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    edit,
    update,
} from '@/actions/App/Http/Controllers/Settings/SystemSettingsController';
import PreferenceSelectFields from '@/components/PreferenceSelectFields.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import { usePermissions } from '@/composables/usePermissions';
import type { PreferenceOptions } from '@/types/preferences';

type SystemSettings = {
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
                <CardTitle>Default preferences</CardTitle>
                <CardDescription>
                    Set the defaults used when new user preferences are created.
                </CardDescription>
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
