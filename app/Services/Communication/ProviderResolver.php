<?php

namespace App\Services\Communication;

use App\Models\CommunicationProviderAccount;
use App\Services\Communication\Data\ProviderContext;
use App\Services\Communication\Data\ResolvedProvider;
use App\Services\Communication\Providers\SimulatedProvider;
use Illuminate\Support\Facades\App;

class ProviderResolver
{
    /** @return list<ResolvedProvider> */
    public function resolve(int $channelId): array
    {
        if (! config('communication.simulation.enabled') || ! App::environment(['local', 'testing'])) {
            return [];
        }

        return array_values(CommunicationProviderAccount::query()->with('provider.channel')
            ->where('is_active', true)
            ->whereHas('provider', fn ($query) => $query->where('channel_id', $channelId)->where('is_active', true)
                ->whereHas('channel', fn ($channel) => $channel->where('is_active', true)))
            ->get()->sortBy([
                ['priority', 'asc'],
                fn ($a, $b) => $a->provider->position <=> $b->provider->position,
                ['id', 'asc'],
            ])->filter(function (CommunicationProviderAccount $account): bool {
                foreach ($account->provider->fields as $field) {
                    if ($field['required'] && blank($account->credentials[$field['key']] ?? null)) {
                        return false;
                    }
                }

                return in_array($account->provider->channel->code, ['sms', 'voice', 'email'], true);
            })->map(function (CommunicationProviderAccount $account): ResolvedProvider {
                $outcomes = config('communication.simulation.outcomes', []);
                $outcome = $outcomes[$account->public_id]
                    ?? $outcomes[$account->provider->channel->code.'.'.$account->provider->code]
                    ?? 'success';

                return new ResolvedProvider($account->id, new SimulatedProvider(new ProviderContext(
                    $account->id, $account->public_id, $account->credentials ?? [], $outcome,
                )), simulated: true);
            })->values()->all());
    }
}
