import { usePage } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import customParseFormat from 'dayjs/plugin/customParseFormat';
import timezone from 'dayjs/plugin/timezone';
import utc from 'dayjs/plugin/utc';
import { computed } from 'vue';
import type { UserPreferences } from '@/types/preferences';

dayjs.extend(utc);
dayjs.extend(timezone);
dayjs.extend(customParseFormat);

const fallbackPreferences: UserPreferences = {
    values: null,
    country: null,
    timezone: {
        id: 0,
        name: 'utc',
        identifier: 'UTC',
        display_name: 'UTC',
        short_code: 'UTC',
    },
    language: null,
    formats: {
        number: null,
        date: {
            id: 0,
            type: 'date',
            name: 'iso-date',
            display_name: 'ISO date',
            format: 'Y-m-d',
            client_format: 'YYYY-MM-DD',
            example: '2026-09-09',
        },
        time: {
            id: 0,
            type: 'time',
            name: 'twenty-four-hour-time',
            display_name: '24-hour time',
            format: 'H:i',
            client_format: 'HH:mm',
            example: '17:30',
        },
    },
};

type DateTimeValue = string | Date | null | undefined;

export function useDateTimeFormat() {
    const page = usePage();

    const preferences = computed<UserPreferences>(
        () => page.props.auth?.preferences ?? fallbackPreferences,
    );

    const userTimezone = computed(
        () => preferences.value.timezone?.identifier ?? 'UTC',
    );

    const dateFormat = computed(
        () => preferences.value.formats.date?.client_format ?? 'YYYY-MM-DD',
    );

    const timeFormat = computed(
        () => preferences.value.formats.time?.client_format ?? 'HH:mm',
    );

    const dateTimeFormat = computed(
        () => `${dateFormat.value} ${timeFormat.value}`,
    );

    const formatUtc = (
        value: DateTimeValue,
        format: string,
    ): string | null => {
        if (!value) {
            return null;
        }

        const date = dayjs.utc(value);

        if (!date.isValid()) {
            return null;
        }

        return date.tz(userTimezone.value).format(format);
    };

    const formatDate = (value: DateTimeValue): string | null =>
        formatUtc(value, dateFormat.value);

    const formatTime = (value: DateTimeValue): string | null =>
        formatUtc(value, timeFormat.value);

    const formatDateTime = (value: DateTimeValue): string | null =>
        formatUtc(value, dateTimeFormat.value);

    const toUtcIso = (
        localValue: string | null | undefined,
        format = dateTimeFormat.value,
    ): string | null => {
        if (!localValue) {
            return null;
        }

        const date = dayjs.tz(localValue, format, userTimezone.value);

        if (!date.isValid()) {
            return null;
        }

        return date.utc().toISOString();
    };

    return {
        preferences,
        userTimezone,
        dateFormat,
        timeFormat,
        dateTimeFormat,
        formatDate,
        formatTime,
        formatDateTime,
        toUtcIso,
    };
}
