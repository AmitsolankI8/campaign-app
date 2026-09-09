<?php

namespace App\Concerns;

use App\Models\PreferenceCountry;
use App\Models\PreferenceFormat;
use App\Models\PreferenceLanguage;
use App\Models\PreferenceTimezone;
use Illuminate\Validation\Rule;

trait PreferenceValidationRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    protected function preferenceRules(string $prefix = ''): array
    {
        return [
            $prefix.'country_preference_id' => ['required', 'integer', Rule::exists((new PreferenceCountry)->getTable(), 'id')],
            $prefix.'timezone_preference_id' => ['required', 'integer', Rule::exists((new PreferenceTimezone)->getTable(), 'id')],
            $prefix.'language_preference_id' => ['required', 'integer', Rule::exists((new PreferenceLanguage)->getTable(), 'id')],
            $prefix.'number_format_preference_id' => ['required', 'integer', Rule::exists((new PreferenceFormat)->getTable(), 'id')->where('type', PreferenceFormat::TYPE_NUMBER)],
            $prefix.'date_format_preference_id' => ['required', 'integer', Rule::exists((new PreferenceFormat)->getTable(), 'id')->where('type', PreferenceFormat::TYPE_DATE)],
            $prefix.'time_format_preference_id' => ['required', 'integer', Rule::exists((new PreferenceFormat)->getTable(), 'id')->where('type', PreferenceFormat::TYPE_TIME)],
        ];
    }
}
