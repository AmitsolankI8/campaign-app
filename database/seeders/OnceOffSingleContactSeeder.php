<?php

namespace Database\Seeders;

use App\Enums\ContactUploadMode;
use App\Models\Campaigns\OnceOffCampaign;
use App\Models\User;
use App\Support\ChangeOnceOffCampaignStatus;
use App\Support\SaveOnceOffCampaignSchedules;
use App\Support\StageCampaignContactUpload;
use App\Support\SyncOnceOffCampaignContactImport;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OnceOffSingleContactSeeder extends Seeder
{
    /** @var array{first_name: string, last_name: string, number: string, email: string} */
    private const CONTACT = [
        'first_name' => 'Single',
        'last_name' => 'Contact',
        'number' => '+919999999999',
        'email' => 'single-contact@yopmail.com',
    ];

    public function __construct(
        private StageCampaignContactUpload $stageContactUpload,
        private SyncOnceOffCampaignContactImport $syncContactImport,
        private SaveOnceOffCampaignSchedules $saveSchedules,
        private ChangeOnceOffCampaignStatus $changeStatus,
    ) {}

    public function run(): void
    {
        $uploader = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $scheduledAt = now()->addMinutes(2)->utc();

        $campaign = DB::transaction(function () use ($uploader, $scheduledAt): OnceOffCampaign {
            $campaign = OnceOffCampaign::factory()->create([
                'public_id' => (string) Str::ulid(),
                'name' => 'Single Contact Email Test '.$scheduledAt->format('Y-m-d H:i:s').' UTC',
                'short_note' => 'Seeded for a one-contact email campaign test.',
            ]);

            $contact = self::CONTACT;
            $contactImport = $this->stageContactUpload->handle(
                $campaign,
                $uploader,
                ContactUploadMode::Append,
                [[
                    ...$contact,
                    'row_number' => 1,
                    'normalized_number' => preg_replace('/\D/', '', $contact['number']),
                ]],
            );

            $this->syncContactImport->handle($campaign, $contactImport);
            $this->saveSchedules->handle($campaign, [[
                'scheduled_at' => $scheduledAt->toJSON(),
                'channel' => 'email',
            ]]);
            $this->changeStatus->launch($campaign);

            return $campaign;
        });

        $this->command->info("Created and launched {$campaign->name} ({$campaign->public_id}).");
        $this->command->info("Email is scheduled for {$scheduledAt->toIso8601String()}.");
    }
}
