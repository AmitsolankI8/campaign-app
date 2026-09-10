<?php

namespace App\Http\Requests\Settings;

use App\Models\CommunicationProvider;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateCommunicationProviderRequest extends FormRequest
{
    private ?CommunicationProvider $providerRecord = null;

    public function communicationProvider(): CommunicationProvider
    {
        return $this->providerRecord ??= CommunicationProvider::query()
            ->where('channel', (string) $this->route('channel'))
            ->where('provider', (string) $this->route('provider'))
            ->firstOrFail();
    }

    public function authorize(): bool
    {
        return $this->user()?->can('communication.view') === true
            && $this->user()->can('communication.edit') === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $fields = $this->communicationProvider()->fields;
        $rules = [
            'is_active' => ['required', 'boolean'],
            'priority' => ['required', 'integer', 'min:1', 'max:999'],
            'credentials' => ['required', 'array:'.implode(',', array_column($fields, 'key'))],
        ];
        foreach ($fields as $field) {
            $rules['credentials.'.$field['key']] = ['nullable', ...$field['rules']];
        }

        return $rules;
    }

    /** @return list<\Closure> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty() || ! $this->boolean('is_active')) {
                return;
            }
            $saved = $this->communicationProvider()->credentials ?? [];
            foreach ($this->communicationProvider()->fields as $field) {
                $value = $this->input('credentials.'.$field['key']);
                if ($field['secret'] && blank($value)) {
                    $value = $saved[$field['key']] ?? null;
                }
                if ($field['required'] && blank($value)) {
                    $validator->errors()->add('credentials.'.$field['key'], $field['label'].' is required to activate this provider.');
                }
            }
        }];
    }
}
