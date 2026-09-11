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
            'number' => ['required', 'string', 'max:32', 'regex:/^\+?[0-9][0-9\s().-]*$/'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
        ];
    }
}
