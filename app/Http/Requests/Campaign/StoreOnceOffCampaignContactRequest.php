<?php

namespace App\Http\Requests\Campaign;

use App\Support\CampaignContactRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreOnceOffCampaignContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('campaigns.view') === true
            && $this->user()->can('campaigns.edit');
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return CampaignContactRules::rules();
    }
}
