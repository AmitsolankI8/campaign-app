<?php

use App\Enums\CampaignStatus;
use App\Enums\CampaignType;
use App\Enums\ContactUploadMode;
use App\Models\Campaign;
use App\Models\Campaigns\OnceOffCampaign;
use App\Models\OnceOffCampaignContactImport;
use App\Models\User;
use App\Support\CampaignContactSyncPlan;
use App\Support\ChangeOnceOffCampaignStatus;
use App\Support\CommunicationRegistry;
use App\Support\SaveOnceOffCampaignSchedules;
use App\Support\StageCampaignContactUpload;
use App\Support\SyncOnceOffCampaignContactImport;
use Illuminate\Database\Eloquent\ModelNotFoundException;

test('once-off models share the campaign table and default to once-off draft records', function () {
    $campaign = OnceOffCampaign::create(['name' => 'Once-off campaign']);
    $factoryCampaign = OnceOffCampaign::factory()->create();
    $genericCampaign = Campaign::factory()->ongoing()->create();

    expect($campaign->fresh()->campaign_type)->toBe(CampaignType::OnceOff)
        ->and($campaign->fresh()->status)->toBe(CampaignStatus::Draft)
        ->and($campaign->public_id)->not->toBeEmpty()
        ->and($campaign->getRouteKey())->toBe($campaign->public_id)
        ->and($factoryCampaign)->toBeInstanceOf(OnceOffCampaign::class)
        ->and($genericCampaign)->toBeInstanceOf(Campaign::class)
        ->and($genericCampaign)->not->toBeInstanceOf(OnceOffCampaign::class)
        ->and(Campaign::query()->count())->toBe(3)
        ->and(OnceOffCampaign::query()->count())->toBe(2);
});

test('once-off queries and public route binding exclude other campaign types', function (CampaignType $type) {
    $campaign = Campaign::factory()->onceOff()->create();
    $other = Campaign::factory()->create(['campaign_type' => $type]);

    expect(OnceOffCampaign::query()->find($campaign->id))->toBeInstanceOf(OnceOffCampaign::class)
        ->and(OnceOffCampaign::query()->find($other->id))->toBeNull()
        ->and((new OnceOffCampaign)->resolveRouteBinding($campaign->public_id)?->id)->toBe($campaign->id)
        ->and((new OnceOffCampaign)->resolveRouteBinding($other->public_id))->toBeNull();
})->with([CampaignType::Ongoing, CampaignType::BatchProcessing]);

test('once-off model saves reject a different campaign type', function (CampaignType $type) {
    expect(fn () => OnceOffCampaign::create(['name' => 'Invalid type', 'campaign_type' => $type]))
        ->toThrow(LogicException::class);
    expect(Campaign::query()->count())->toBe(0);

    $campaign = OnceOffCampaign::factory()->create();
    expect(fn () => $campaign->update(['campaign_type' => $type]))->toThrow(LogicException::class);
    expect($campaign->fresh()->campaign_type)->toBe(CampaignType::OnceOff);
})->with([CampaignType::Ongoing, CampaignType::BatchProcessing]);

test('once-off relationships use campaign ids and resolve their parent as a once-off model', function () {
    $campaign = OnceOffCampaign::factory()->create();
    $other = OnceOffCampaign::factory()->create();
    $contact = $campaign->contacts()->create(['first_name' => 'Alice', 'number' => '+14155550101']);
    $upload = $campaign->contactImports()->create(['file_name' => 'Manual contact', 'contact_count' => 0, 'uploaded_by' => User::factory()->create()->id]);
    $schedule = $campaign->schedules()->create([
        'attempt_count' => 1,
        'scheduled_at' => '2030-01-15T09:30:00.000Z',
        'channel' => array_key_first(CommunicationRegistry::channels()),
    ]);

    foreach ([$contact, $upload, $schedule] as $child) {
        expect($child->campaign_id)->toBe($campaign->id)
            ->and($child->fresh()->campaign)->toBeInstanceOf(OnceOffCampaign::class)
            ->and($child->campaign->id)->toBe($campaign->id);
    }

    expect($campaign->firstSchedule->id)->toBe($schedule->id)
        ->and(Campaign::query()->findOrFail($campaign->id)->firstOnceOffSchedule->id)->toBe($schedule->id)
        ->and($other->contacts()->count())->toBe(0)
        ->and($other->contactImports()->count())->toBe(0)
        ->and($other->schedules()->count())->toBe(0)
        ->and($other->firstSchedule)->toBeNull();
});

test('once-off services recheck the stored campaign type before changing related records', function (string $operation) {
    $campaign = OnceOffCampaign::factory()->create();
    $user = User::factory()->create();
    $upload = $campaign->contactImports()->create(['file_name' => 'Manual contact', 'contact_count' => 0, 'uploaded_by' => $user->id]);
    $before = $upload->fresh()->getAttributes();
    Campaign::query()->whereKey($campaign->id)->update(['campaign_type' => CampaignType::Ongoing]);

    expect(fn () => match ($operation) {
        'schedule' => app(SaveOnceOffCampaignSchedules::class)->handle($campaign, []),
        'stage' => app(StageCampaignContactUpload::class)->handle($campaign, $user, ContactUploadMode::Append, []),
        'plan' => app(CampaignContactSyncPlan::class)->make($campaign, $upload),
        'sync' => app(SyncOnceOffCampaignContactImport::class)->handle($campaign, $upload),
        'launch' => app(ChangeOnceOffCampaignStatus::class)->launch($campaign),
        'stop' => app(ChangeOnceOffCampaignStatus::class)->stop($campaign),
    })->toThrow(ModelNotFoundException::class);

    expect(Campaign::query()->findOrFail($campaign->id)->status)->toBe(CampaignStatus::Draft)
        ->and(OnceOffCampaignContactImport::query()->count())->toBe(1)
        ->and($upload->fresh()->getAttributes())->toBe($before);
})->with(['schedule', 'stage', 'plan', 'sync', 'launch', 'stop']);
