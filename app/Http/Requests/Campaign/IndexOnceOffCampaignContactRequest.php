<?php

namespace App\Http\Requests\Campaign;

use App\Http\Requests\DataTableRequest;

class IndexOnceOffCampaignContactRequest extends DataTableRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('campaigns.view') === true;
    }

    /** @return array<string, string> */
    public function searchableColumns(): array
    {
        return ['first_name' => 'first_name', 'last_name' => 'last_name', 'number' => 'number', 'email' => 'email'];
    }

    /** @return array<string, string> */
    public function sortableColumns(): array
    {
        return [...$this->searchableColumns(), 'created_at' => 'created_at'];
    }
}
