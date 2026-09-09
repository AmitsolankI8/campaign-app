<?php

namespace App\Http\Resources\UserManagement;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'created_at' => $this->created_at?->toJSON(),
            'roles' => $this->roles->pluck('display_name')->values()->all(),
            'can_delete' => ! $this->hasRole('admin') && ! $this->is($request->user()),
            'can_edit' => ! $this->hasRole('admin') || $this->is($request->user()),
        ];
    }
}
