<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type {
    PreferenceOptions,
    UserPreferenceValues,
} from '@/types/preferences';

defineProps<{
    preferences: UserPreferenceValues;
    errors: Partial<Record<keyof UserPreferenceValues, string>>;
    options: PreferenceOptions;
}>();
</script>

<template>
    <div class="grid gap-6 md:grid-cols-2">
        <div class="grid gap-2">
            <Label for="country_preference_id" required>Country</Label>
            <Select
                name="country_preference_id"
                :default-value="String(preferences.country_preference_id)"
            >
                <SelectTrigger id="country_preference_id" class="w-full">
                    <SelectValue placeholder="Select country" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="country in options.countries"
                        :key="country.id"
                        :value="String(country.id)"
                    >
                        {{ country.display_name }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <InputError :message="errors.country_preference_id" />
        </div>

        <div class="grid gap-2">
            <Label for="timezone_preference_id" required>Timezone</Label>
            <Select
                name="timezone_preference_id"
                :default-value="String(preferences.timezone_preference_id)"
            >
                <SelectTrigger id="timezone_preference_id" class="w-full">
                    <SelectValue placeholder="Select timezone" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="timezone in options.timezones"
                        :key="timezone.id"
                        :value="String(timezone.id)"
                    >
                        {{ timezone.display_name }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <InputError :message="errors.timezone_preference_id" />
        </div>

        <div class="grid gap-2">
            <Label for="language_preference_id" required>Language</Label>
            <Select
                name="language_preference_id"
                :default-value="String(preferences.language_preference_id)"
            >
                <SelectTrigger id="language_preference_id" class="w-full">
                    <SelectValue placeholder="Select language" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="language in options.languages"
                        :key="language.id"
                        :value="String(language.id)"
                    >
                        {{ language.display_name }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <InputError :message="errors.language_preference_id" />
        </div>

        <div class="grid gap-2">
            <Label for="number_format_preference_id" required
                >Number format</Label
            >
            <Select
                name="number_format_preference_id"
                :default-value="String(preferences.number_format_preference_id)"
            >
                <SelectTrigger id="number_format_preference_id" class="w-full">
                    <SelectValue placeholder="Select number format" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="format in options.numberFormats"
                        :key="format.id"
                        :value="String(format.id)"
                    >
                        {{ format.display_name }}: {{ format.example }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <InputError :message="errors.number_format_preference_id" />
        </div>

        <div class="grid gap-2">
            <Label for="date_format_preference_id" required>Date format</Label>
            <Select
                name="date_format_preference_id"
                :default-value="String(preferences.date_format_preference_id)"
            >
                <SelectTrigger id="date_format_preference_id" class="w-full">
                    <SelectValue placeholder="Select date format" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="format in options.dateFormats"
                        :key="format.id"
                        :value="String(format.id)"
                    >
                        {{ format.display_name }}: {{ format.example }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <InputError :message="errors.date_format_preference_id" />
        </div>

        <div class="grid gap-2">
            <Label for="time_format_preference_id" required>Time format</Label>
            <Select
                name="time_format_preference_id"
                :default-value="String(preferences.time_format_preference_id)"
            >
                <SelectTrigger id="time_format_preference_id" class="w-full">
                    <SelectValue placeholder="Select time format" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="format in options.timeFormats"
                        :key="format.id"
                        :value="String(format.id)"
                    >
                        {{ format.display_name }}: {{ format.example }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <InputError :message="errors.time_format_preference_id" />
        </div>
    </div>
</template>
