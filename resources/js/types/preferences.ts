export type PreferenceOption = {
    id: number;
    name: string;
    identifier: string;
    display_name: string;
    short_code: string;
};

export type FormatPreferenceOption = {
    id: number;
    type: 'number' | 'date' | 'time';
    name: string;
    display_name: string;
    format: string;
    example: string;
};

export type PreferenceOptions = {
    countries: PreferenceOption[];
    timezones: PreferenceOption[];
    languages: PreferenceOption[];
    numberFormats: FormatPreferenceOption[];
    dateFormats: FormatPreferenceOption[];
    timeFormats: FormatPreferenceOption[];
};

export type UserPreferenceValues = {
    country_preference_id: number;
    timezone_preference_id: number;
    language_preference_id: number;
    number_format_preference_id: number;
    date_format_preference_id: number;
    time_format_preference_id: number;
};

export type UserPreferenceFormat = FormatPreferenceOption & {
    client_format: string;
};

export type UserPreferences = {
    values: UserPreferenceValues | null;
    country: PreferenceOption | null;
    timezone: PreferenceOption | null;
    language: PreferenceOption | null;
    formats: {
        number: UserPreferenceFormat | null;
        date: UserPreferenceFormat | null;
        time: UserPreferenceFormat | null;
    };
};
