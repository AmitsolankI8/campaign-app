<?php

namespace App\Http\Requests\Campaign;

use Illuminate\Foundation\Http\FormRequest;

class SyncContactUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('campaigns.view') === true && $this->user()->can('campaigns.edit');
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'row_ids' => ['sometimes', 'array', 'min:1', 'max:5000'],
            'row_ids.*' => ['required', 'string', 'ulid', 'distinct'],
            'fingerprint' => ['nullable', 'string', 'size:64'],
        ];
    }
}
