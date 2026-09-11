<?php

namespace Database\Seeders;

use App\Enums\CampaignType;
use App\Models\Campaign;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CampaignSeeder extends Seeder
{
    /**
     * @var array<string, array{name: string, short_note: string}>
     */
    private const CAMPAIGNS = [
        'once_off' => [
            'name' => 'Once-Off Campaign',
            'short_note' => 'A sample one-time outreach campaign.',
        ],
        'ongoing' => [
            'name' => 'Ongoing Campaign',
            'short_note' => 'A sample recurring outreach campaign.',
        ],
        'batch_processing' => [
            'name' => 'Batch Processing Campaign',
            'short_note' => 'A sample bulk processing campaign.',
        ],
    ];

    public function run(): void
    {
        foreach (CampaignType::cases() as $type) {
            $campaign = self::CAMPAIGNS[$type->key()];

            if (Campaign::query()
                ->where('name', $campaign['name'])
                ->where('campaign_type', $type->value)
                ->exists()) {
                continue;
            }

            $factory = match ($type) {
                CampaignType::OnceOff => Campaign::factory()->onceOff(),
                CampaignType::Ongoing => Campaign::factory()->ongoing(),
                CampaignType::BatchProcessing => Campaign::factory()->batchProcessing(),
            };

            $factory->create([
                'public_id' => (string) Str::ulid(),
                'name' => $campaign['name'],
                'short_note' => $campaign['short_note'],
            ]);
        }
    }
}
