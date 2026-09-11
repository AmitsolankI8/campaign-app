<?php

namespace App\Http\Requests\Campaign;

use App\Enums\ContactImportStatus;
use App\Http\Requests\DataTableRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class IndexOnceOffCampaignContactImportRequest extends DataTableRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('campaigns.view') === true
            && $this->user()->can('campaigns.edit');
    }

    public function filterRules(): array
    {
        return ['status' => ['nullable', 'integer', Rule::in(ContactImportStatus::values())]];
    }

    public function filterDefaults(): array
    {
        return ['status' => ContactImportStatus::DEFAULT];
    }

    public function applyFilters(Builder $query, array $filters): void
    {
        if ($filters['status'] !== null) {
            $query->where('status', $filters['status']);
        }
    }

    /** @return array<string, string> */
    public function searchableColumns(): array
    {
        return ['file_name' => 'file_name'];
    }

    /** @return array<string, string> */
    public function sortableColumns(): array
    {
        return ['file_name' => 'file_name', 'contact_count' => 'contact_count', 'status' => 'status', 'created_at' => 'created_at', 'synced_at' => 'synced_at'];
    }
}
