<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { index } from '@/actions/App/Http/Controllers/Settings/PreferencesController';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type {
    FormatPreferenceOption,
    PreferenceOption,
    UserPreferenceValues,
} from '@/types/preferences';

type Preferences = {
    countries: PreferenceOption[];
    timezones: PreferenceOption[];
    languages: PreferenceOption[];
    formats: {
        number: FormatPreferenceOption[];
        date: FormatPreferenceOption[];
        time: FormatPreferenceOption[];
    };
};

defineProps<{
    preferences: Preferences;
    defaultPreferences: UserPreferenceValues;
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Preferences', href: index() }] },
});

const formatDefaultFields = {
    number: 'number_format_preference_id',
    date: 'date_format_preference_id',
    time: 'time_format_preference_id',
} as const;

const isDefaultFormat = (
    format: FormatPreferenceOption,
    defaults: UserPreferenceValues,
): boolean => defaults[formatDefaultFields[format.type]] === format.id;
</script>

<template>
    <Head title="Preferences" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Preferences</h1>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Countries</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="overflow-hidden rounded-xl border">
                    <table class="w-full text-sm">
                        <thead class="bg-muted/50 text-left">
                            <tr>
                                <th class="px-4 py-3 font-medium">Name</th>
                                <th class="px-4 py-3 font-medium">
                                    Identifier
                                </th>
                                <th class="px-4 py-3 font-medium">
                                    Display name
                                </th>
                                <th class="px-4 py-3 font-medium">
                                    Short code
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="country in preferences.countries"
                                :key="country.id"
                                class="border-t"
                            >
                                <td class="px-4 py-3">
                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <span class="font-medium">
                                            {{ country.name }}
                                        </span>
                                        <Badge
                                            v-if="
                                                defaultPreferences.country_preference_id ===
                                                country.id
                                            "
                                            variant="secondary"
                                        >
                                            Default
                                        </Badge>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">
                                    {{ country.identifier }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ country.display_name }}
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">
                                    {{ country.short_code }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Timezones</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="overflow-hidden rounded-xl border">
                    <table class="w-full text-sm">
                        <thead class="bg-muted/50 text-left">
                            <tr>
                                <th class="px-4 py-3 font-medium">Name</th>
                                <th class="px-4 py-3 font-medium">
                                    Identifier
                                </th>
                                <th class="px-4 py-3 font-medium">
                                    Display name
                                </th>
                                <th class="px-4 py-3 font-medium">
                                    Short code
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="timezone in preferences.timezones"
                                :key="timezone.id"
                                class="border-t"
                            >
                                <td class="px-4 py-3">
                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <span class="font-medium">
                                            {{ timezone.name }}
                                        </span>
                                        <Badge
                                            v-if="
                                                defaultPreferences.timezone_preference_id ===
                                                timezone.id
                                            "
                                            variant="secondary"
                                        >
                                            Default
                                        </Badge>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">
                                    {{ timezone.identifier }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ timezone.display_name }}
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">
                                    {{ timezone.short_code }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Languages</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="overflow-hidden rounded-xl border">
                    <table class="w-full text-sm">
                        <thead class="bg-muted/50 text-left">
                            <tr>
                                <th class="px-4 py-3 font-medium">Name</th>
                                <th class="px-4 py-3 font-medium">
                                    Identifier
                                </th>
                                <th class="px-4 py-3 font-medium">
                                    Display name
                                </th>
                                <th class="px-4 py-3 font-medium">
                                    Short code
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="language in preferences.languages"
                                :key="language.id"
                                class="border-t"
                            >
                                <td class="px-4 py-3">
                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <span class="font-medium">
                                            {{ language.name }}
                                        </span>
                                        <Badge
                                            v-if="
                                                defaultPreferences.language_preference_id ===
                                                language.id
                                            "
                                            variant="secondary"
                                        >
                                            Default
                                        </Badge>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">
                                    {{ language.identifier }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ language.display_name }}
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">
                                    {{ language.short_code }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Formatting</CardTitle>
            </CardHeader>
            <CardContent class="grid gap-6">
                <section
                    v-for="(formats, type) in preferences.formats"
                    :key="type"
                    class="grid gap-3"
                >
                    <h2 class="text-base font-medium capitalize">{{ type }}</h2>
                    <div class="overflow-hidden rounded-xl border">
                        <table class="w-full text-sm">
                            <thead class="bg-muted/50 text-left">
                                <tr>
                                    <th class="px-4 py-3 font-medium">Name</th>
                                    <th class="px-4 py-3 font-medium">
                                        Display name
                                    </th>
                                    <th class="px-4 py-3 font-medium">
                                        Format
                                    </th>
                                    <th class="px-4 py-3 font-medium">
                                        Example
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="format in formats"
                                    :key="format.id"
                                    class="border-t"
                                >
                                    <td class="px-4 py-3">
                                        <div
                                            class="flex flex-wrap items-center gap-2"
                                        >
                                            <span class="font-medium">
                                                {{ format.name }}
                                            </span>
                                            <Badge
                                                v-if="
                                                    isDefaultFormat(
                                                        format,
                                                        defaultPreferences,
                                                    )
                                                "
                                                variant="secondary"
                                            >
                                                Default
                                            </Badge>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ format.display_name }}
                                    </td>
                                    <td
                                        class="px-4 py-3 font-mono text-muted-foreground"
                                    >
                                        {{ format.format }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ format.example }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </CardContent>
        </Card>
    </div>
</template>
