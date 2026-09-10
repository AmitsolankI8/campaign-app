<?php

namespace App\Http\Requests\UserManagement;

use App\Http\Requests\DataTableRequest;
use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class IndexUserRequest extends DataTableRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('users.view') === true;
    }

    public function filterRules(): array
    {
        return ['role' => ['nullable', 'string', Rule::exists(Role::class, 'name')->where('guard_name', 'web')]];
    }

    public function filterDefaults(): array
    {
        return ['role' => null];
    }

    public function applyFilters(Builder $query, array $filters): void
    {
        if ($filters['role'] !== null) {
            $query->whereHas('roles', fn (Builder $roles) => $roles
                ->where('name', $filters['role'])->where('guard_name', 'web'));
        }
    }

    public function searchableColumns(): array
    {
        return [
            'full_name' => function (Builder $query, string $search): void {
                foreach (explode(' ', $search) as $word) {
                    if ($word !== '') {
                        $query->where(fn (Builder $name) => $name
                            ->whereLike('first_name', '%'.$word.'%')
                            ->orWhereLike('last_name', '%'.$word.'%'));
                    }
                }
            },
            'email' => 'email',
        ];
    }

    public function sortableColumns(): array
    {
        return [
            'full_name' => ['first_name', 'last_name'],
            'email' => 'email',
            'created_at' => 'created_at',
        ];
    }
}
