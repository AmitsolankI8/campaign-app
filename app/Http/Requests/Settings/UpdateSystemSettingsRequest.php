<?php

namespace App\Http\Requests\Settings;

use App\Concerns\PreferenceValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSystemSettingsRequest extends FormRequest
{
    use PreferenceValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('system-settings.view') === true
            && $this->user()->can('system-settings.edit') === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'display_name' => ['required', 'string', 'max:255'],
            ...$this->preferenceRules('default_'),
        ];
    }
}
