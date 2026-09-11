<?php

namespace App\Http\Requests\Campaign;

use Illuminate\Foundation\Http\FormRequest;

class StoreOnceOffCampaignContactImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('campaigns.view') === true
            && $this->user()->can('campaigns.edit');
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['file' => ['required', 'file', 'extensions:csv,xlsx,xls', 'mimes:csv,txt,xlsx,xls', 'max:2048']];
    }
}
