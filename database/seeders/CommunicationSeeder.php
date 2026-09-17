<?php

namespace Database\Seeders;

use App\Models\CommunicationChannel;
use App\Models\CommunicationProvider;
use App\Models\CommunicationProviderAccount;
use App\Support\CommunicationRegistry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CommunicationSeeder extends Seeder
{
    public function run(): void
    {
        foreach (CommunicationRegistry::providers() as $attributes) {
            $channel = CommunicationChannel::query()->firstOrNew(['code' => $attributes['channel']]);
            if (! $channel->exists) {
                $channel->public_id = (string) Str::ulid();
            }
            $channel->fill(['name' => $attributes['channel_name'], 'position' => $attributes['channel_position']])->save();
            $record = CommunicationProvider::query()->firstOrNew([
                'channel_id' => $channel->id,
                'code' => $attributes['provider'],
            ]);

            if (! $record->exists) {
                $record->public_id = (string) Str::ulid();
            }

            $record->fill(['name' => $attributes['name'], 'position' => $attributes['position'], 'fields' => $attributes['fields']])->save();
            $account = CommunicationProviderAccount::query()->firstOrNew(['provider_id' => $record->id, 'key' => 'default']);
            if (! $account->exists) {
                $account->public_id = (string) Str::ulid();
                $account->priority = $attributes['position'];
                $account->save();
            }
        }
    }
}
