<?php

namespace App\Http\Requests\Campaign;

use App\Enums\ContactUploadMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOnceOffCampaignContactImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('campaigns.view') === true
            && $this->user()->can('campaigns.edit');
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'extensions:csv,xlsx,xls', 'mimes:csv,txt,xlsx,xls', 'max:2048'],
            'mode' => ['required', 'integer', Rule::in(ContactUploadMode::values())],
        ];
    }
}
