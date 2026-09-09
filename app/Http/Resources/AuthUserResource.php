<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class AuthUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at?->toJSON(),
            'created_at' => $this->created_at?->toJSON(),
            'updated_at' => $this->updated_at?->toJSON(),
            'roles' => $this->roles->map(fn ($role) => [
                'id' => $role->public_id,
                'name' => $role->name,
                'display_name' => $role->display_name,
                'short_note' => $role->short_note,
                'guard_name' => $role->guard_name,
            ])->values()->all(),
        ];
    }
}
