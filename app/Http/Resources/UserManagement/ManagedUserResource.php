<?php

namespace App\Http\Resources\UserManagement;

use App\Models\User;
use App\Settings\SystemSettings;
use App\Support\PreferenceOptions;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class ManagedUserResource extends JsonResource
{
    public function __construct($resource, private readonly SystemSettings $settings)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'roles' => $this->roles->pluck('name')->values()->all(),
            'roles_locked' => $this->hasRole('admin'),
            'can_edit' => ! $this->hasRole('admin') || $this->is($request->user()),
            'preferences' => PreferenceOptions::values($this->preferences, $this->settings),
        ];
    }
}
