<?php

namespace App\Http\Resources;

use App\Models\CommunicationProvider;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CommunicationProvider */
class CommunicationProviderResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $credentials = $this->credentials ?? [];
        $fields = $this->fields ?? [];

        return [
            'id' => $this->public_id,
            'provider' => $this->provider,
            'name' => $this->name,
            'is_active' => $this->is_active,
            'priority' => $this->priority,
            'fields' => collect($fields)->map(fn (array $field) => collect($field)->except('rules')->all())->all(),
            'credentials' => collect($fields)->mapWithKeys(fn (array $field) => [
                $field['key'] => $field['secret'] ? '' : ($credentials[$field['key']] ?? ''),
            ])->all(),
            'saved_secrets' => collect($fields)->filter(fn (array $field) => $field['secret'] && filled($credentials[$field['key']] ?? null))->pluck('key')->values()->all(),
        ];
    }
}
