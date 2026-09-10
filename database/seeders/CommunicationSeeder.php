<?php

namespace Database\Seeders;

use App\Models\CommunicationProvider;
use App\Support\CommunicationRegistry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CommunicationSeeder extends Seeder
{
    public function run(): void
    {
        foreach (CommunicationRegistry::providers() as $attributes) {
            $record = CommunicationProvider::query()->firstOrNew([
                'channel' => $attributes['channel'],
                'provider' => $attributes['provider'],
            ]);

            if (! $record->exists) {
                $record->public_id = (string) Str::ulid();
                $record->priority = $attributes['position'];
            }

            $record->fill($attributes)->save();
        }
    }
}
