<?php

namespace App\Http\Requests\Campaign;

use App\Enums\CampaignType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('campaigns.create') === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'short_note' => ['nullable', 'string', 'max:255'],
            'campaign_type' => ['required', 'integer', Rule::in(CampaignType::values())],
        ];
    }
}
