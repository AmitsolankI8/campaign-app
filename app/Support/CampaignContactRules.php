<?php

namespace App\Support;

class CampaignContactRules
{
    /** @return array<string, list<string>> */
    public static function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'number' => ['required', 'string', 'max:32', 'regex:/^\+[1-9][0-9\s().-]*$/', 'regex:/^(?:\D*\d){7,15}\D*$/'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        return ['number.regex' => __('Use an international number starting with + and a country code, containing 7 to 15 digits.')];
    }
}
