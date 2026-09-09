<?php

namespace App\Http\Resources\UserManagement;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Role
 */
class ManagedRoleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'name' => $this->name,
            'display_name' => $this->display_name,
            'short_note' => $this->short_note,
            'permissions' => $this->permissions->pluck('name')->values()->all(),
        ];
    }
}
