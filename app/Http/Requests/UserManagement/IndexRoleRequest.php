<?php

namespace App\Http\Requests\UserManagement;

use App\Http\Requests\DataTableRequest;
use Illuminate\Database\Eloquent\Builder;

class IndexRoleRequest extends DataTableRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('roles.view') === true;
    }

    public function searchableColumns(): array
    {
        return [
            'display_name' => function (Builder $query, string $search): void {
                $query->whereLike('display_name', '%'.$search.'%')->orWhereLike('name', '%'.$search.'%');
            },
            'short_note' => 'short_note',
            'users_count' => function (Builder $query, string $search): void {
                if (ctype_digit($search)) {
                    $query->has('users', '=', (int) $search);
                } else {
                    $query->whereRaw('1 = 0');
                }
            },
        ];
    }

    public function sortableColumns(): array
    {
        return [
            'display_name' => 'display_name',
            'short_note' => 'short_note',
            'users_count' => 'users_count',
            'created_at' => 'created_at',
        ];
    }

    public function defaultSort(): string
    {
        return 'display_name';
    }

    public function defaultDirection(): string
    {
        return 'asc';
    }
}
