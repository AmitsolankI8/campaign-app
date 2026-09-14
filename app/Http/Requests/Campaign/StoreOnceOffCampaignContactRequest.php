<?php

namespace App\Http\Requests\Campaign;

use App\Enums\ContactUploadMode;
use App\Support\CampaignContactRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOnceOffCampaignContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('campaigns.view') === true
            && $this->user()->can('campaigns.edit');
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [...CampaignContactRules::rules(), 'mode' => ['required', 'integer', Rule::in([ContactUploadMode::Append->value, ContactUploadMode::Update->value])]];
    }

    public function messages(): array
    {
        return CampaignContactRules::messages();
    }
}
