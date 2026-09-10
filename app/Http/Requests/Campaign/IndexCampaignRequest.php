<?php

namespace App\Http\Requests\Campaign;

use App\Enums\CampaignStatus;
use App\Enums\CampaignType;
use App\Http\Requests\DataTableRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class IndexCampaignRequest extends DataTableRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('campaigns.view') === true;
    }

    public function filterRules(): array
    {
        return [
            'type' => ['nullable', 'integer', Rule::in(CampaignType::values())],
            'status' => ['nullable', 'integer', Rule::in(CampaignStatus::values())],
        ];
    }

    public function filterDefaults(): array
    {
        return [
            'type' => null,
            'status' => null,
        ];
    }

    public function applyFilters(Builder $query, array $filters): void
    {
        if ($filters['type'] !== null) {
            $query->where('campaign_type', $filters['type']);
        }

        if ($filters['status'] !== null) {
            $query->where('status', $filters['status']);
        }
    }

    /**
     * @return array<string, string|\Closure(Builder<*>, string): void>
     */
    public function searchableColumns(): array
    {
        return [
            'name' => 'name',
            'short_note' => 'short_note',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function sortableColumns(): array
    {
        return [
            'name' => 'name',
            'type' => 'campaign_type',
            'status' => 'status',
            'short_note' => 'short_note',
            'created_at' => 'created_at',
        ];
    }
}
