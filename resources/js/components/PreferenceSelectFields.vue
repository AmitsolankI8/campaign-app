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
import type { PreferenceOptions } from '@/types/preferences';

type PreferenceField =
    | 'country_preference_id'
    | 'timezone_preference_id'
    | 'language_preference_id'
    | 'number_format_preference_id'
    | 'date_format_preference_id'
    | 'time_format_preference_id';

type DefaultPreferenceField = `default_${PreferenceField}`;
type PreferenceForm = Partial<
    Record<PreferenceField | DefaultPreferenceField, string | number>
>;
type PreferenceErrors = Partial<
    Record<PreferenceField | DefaultPreferenceField, string>
>;

const props = withDefaults(
    defineProps<{
        form: PreferenceForm;
        errors: PreferenceErrors;
        options: PreferenceOptions;
        fieldPrefix?: 'default_' | '';
    }>(),
    { fieldPrefix: '' },
);

const emit = defineEmits<{
    updateField: [
        field: PreferenceField | DefaultPreferenceField,
        value: string | number,
    ];
}>();

const fieldName = (
    field: PreferenceField,
): PreferenceField | DefaultPreferenceField =>
    `${props.fieldPrefix}${field}` as PreferenceField | DefaultPreferenceField;

const valueFor = (field: PreferenceField): string =>
    String(props.form[fieldName(field)] ?? '');

const updateValue = (field: PreferenceField, value: unknown): void => {
    if (typeof value !== 'string' && typeof value !== 'number') {
        return;
    }

    emit('updateField', fieldName(field), value);
};

const errorFor = (field: PreferenceField): string | undefined =>
    props.errors[fieldName(field)];
</script>

<template>
    <div class="grid gap-6 md:grid-cols-2">
        <div class="grid gap-2">
            <Label for="country_preference_id" required>Country</Label>
            <Select
                :model-value="valueFor('country_preference_id')"
                @update:model-value="
                    (value) => updateValue('country_preference_id', value)
                "
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
            <InputError :message="errorFor('country_preference_id')" />
        </div>

        <div class="grid gap-2">
            <Label for="timezone_preference_id" required>Timezone</Label>
            <Select
                :model-value="valueFor('timezone_preference_id')"
                @update:model-value="
                    (value) => updateValue('timezone_preference_id', value)
                "
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
            <InputError :message="errorFor('timezone_preference_id')" />
        </div>

        <div class="grid gap-2">
            <Label for="language_preference_id" required>Language</Label>
            <Select
                :model-value="valueFor('language_preference_id')"
                @update:model-value="
                    (value) => updateValue('language_preference_id', value)
                "
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
            <InputError :message="errorFor('language_preference_id')" />
        </div>

        <div class="grid gap-2">
            <Label for="number_format_preference_id" required
                >Number format</Label
            >
            <Select
                :model-value="valueFor('number_format_preference_id')"
                @update:model-value="
                    (value) => updateValue('number_format_preference_id', value)
                "
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
            <InputError :message="errorFor('number_format_preference_id')" />
        </div>

        <div class="grid gap-2">
            <Label for="date_format_preference_id" required>Date format</Label>
            <Select
                :model-value="valueFor('date_format_preference_id')"
                @update:model-value="
                    (value) => updateValue('date_format_preference_id', value)
                "
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
            <InputError :message="errorFor('date_format_preference_id')" />
        </div>

        <div class="grid gap-2">
            <Label for="time_format_preference_id" required>Time format</Label>
            <Select
                :model-value="valueFor('time_format_preference_id')"
                @update:model-value="
                    (value) => updateValue('time_format_preference_id', value)
                "
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
            <InputError :message="errorFor('time_format_preference_id')" />
        </div>
    </div>
</template>
