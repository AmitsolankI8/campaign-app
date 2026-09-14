<?php

namespace App\Http\Requests\Campaign;

use App\Enums\ContactUploadRowStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class IndexContactUploadRowRequest extends IndexOnceOffCampaignContactRequest
{
    public function authorize(): bool
    {
        return parent::authorize() && $this->user()->can('campaigns.edit');
    }

    public function filterRules(): array
    {
        return ['status' => ['nullable', 'integer', Rule::in(ContactUploadRowStatus::values())]];
    }

    public function filterDefaults(): array
    {
        return ['status' => null];
    }

    public function applyFilters(Builder $query, array $filters): void
    {
        if ($filters['status'] !== null) {
            $query->where('status', $filters['status']);
        }
    }

    public function sortableColumns(): array
    {
        return [...parent::sortableColumns(), 'row_number' => 'row_number', 'status' => 'status', 'synced_at' => 'synced_at'];
    }

    public function defaultSort(): string
    {
        return 'row_number';
    }

    public function defaultDirection(): string
    {
        return 'asc';
    }
}
