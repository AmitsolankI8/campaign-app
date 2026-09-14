<?php

use App\Enums\CampaignStatus;
use App\Enums\ContactUploadMode;
use App\Models\Campaign;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\CampaignContactSyncPlan;
use App\Support\CommunicationRegistry;
use App\Support\SaveOnceOffCampaignSchedules;
use App\Support\StageCampaignContactUpload;
use App\Support\SyncOnceOffCampaignContactImport;
use Database\Seeders\CommunicationSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
    $this->seed(CommunicationSeeder::class);
    foreach (['campaigns.view', 'campaigns.edit'] as $permission) {
        Permission::factory()->fromRegistry($permission)->create();
    }
    $this->editor = User::factory()->create();
    $this->editor->givePermissionTo(['campaigns.view', 'campaigns.edit']);
    $this->actingAs($this->editor);
    $this->campaign = Campaign::factory()->onceOff()->create();
    $this->attempt = ['scheduled_at' => now()->addDay()->utc()->format('Y-m-d\TH:i:s.v\Z'), 'channel' => array_key_first(CommunicationRegistry::channels())];
    $this->schedule = $this->campaign->onceOffSchedules()->create(['attempt_count' => 1, ...$this->attempt]);
    $this->contact = $this->campaign->onceOffContacts()->create(['first_name' => 'Existing', 'number' => '+14155550101']);
    $this->upload = app(StageCampaignContactUpload::class)->handle(
        $this->campaign, $this->editor, ContactUploadMode::Append,
        [['first_name' => 'New', 'number' => '+14155550102', 'normalized_number' => '14155550102', 'row_number' => 2]],
        draftEditingFile(),
    );
});

function draftEditingFile(): UploadedFile
{
    return UploadedFile::fake()->createWithContent('contacts.csv', "first_name,number\nNew,+14155550102\n");
}

dataset('non-draft campaign statuses', [CampaignStatus::Launched, CampaignStatus::Running, CampaignStatus::Paused, CampaignStatus::Cancelled]);

test('non-draft schedules and upload details remain readable with direct or inherited permissions', function (CampaignStatus $status, bool $inherited) {
    if ($inherited) {
        $user = User::factory()->create();
        $role = Role::factory()->create();
        $role->givePermissionTo(['campaigns.view', 'campaigns.edit']);
        $user->assignRole($role);
        $this->actingAs($user);
    }
    $this->campaign->update(['status' => $status]);
    $this->get(route('campaigns.once-off.schedule.show', $this->campaign))->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('campaign.status', $status->toArray())
            ->where('schedules.0.id', $this->schedule->public_id));
    $this->get(route('campaigns.once-off.contact-imports.index', [$this->campaign, 'imports' => ['search' => 'contacts']]))
        ->assertSuccessful()->assertInertia(fn (Assert $page) => $page
        ->where('campaign.status', $status->toArray())
        ->where('contactImports.data.0.id', $this->upload->public_id));
    $this->get(route('campaigns.once-off.contact-imports.show', [$this->campaign, $this->upload]))
        ->assertSuccessful()->assertInertia(fn (Assert $page) => $page
        ->where('campaign.status', $status->toArray())
        ->has('rows.data', 1)->has('syncPlan')
        ->where('upload.id', $this->upload->public_id));
    $this->get(route('campaigns.once-off.contact-imports.download', [$this->campaign, $this->upload]))
        ->assertDownload('contacts.csv');
})->with('non-draft campaign statuses')->with([false, true]);

test('non-draft campaigns reject schedule saves manual contacts and file validation or staging without writes', function (CampaignStatus $status) {
    $this->campaign->update(['status' => $status]);
    $schedule = $this->schedule->fresh()->getAttributes();
    $files = Storage::disk('local')->allFiles();
    $this->putJson(route('campaigns.once-off.schedule.update', $this->campaign), ['schedules' => [
        ['id' => $this->schedule->public_id, ...$this->attempt, 'scheduled_at' => now()->addDays(2)->utc()->format('Y-m-d\TH:i:s.v\Z')],
        [...$this->attempt, 'scheduled_at' => now()->addDays(3)->utc()->format('Y-m-d\TH:i:s.v\Z')],
    ]])->assertUnprocessable()->assertJsonValidationErrors('schedules');
    foreach ([ContactUploadMode::Append, ContactUploadMode::Update] as $mode) {
        $this->postJson(route('campaigns.once-off.contacts.store', $this->campaign), [
            'first_name' => 'Manual', 'number' => '+14155550103', 'mode' => $mode->value,
        ])->assertUnprocessable()->assertJsonValidationErrors('mode');
    }
    foreach (ContactUploadMode::cases() as $mode) {
        foreach (['preview', 'store'] as $action) {
            $this->postJson(route('campaigns.once-off.contact-imports.'.$action, $this->campaign), [
                'file' => draftEditingFile(), 'mode' => $mode->value,
            ])->assertUnprocessable()->assertJsonValidationErrors('mode');
        }
    }
    expect($this->schedule->fresh()->getAttributes())->toBe($schedule)
        ->and($this->campaign->onceOffSchedules()->count())->toBe(1)
        ->and($this->campaign->contactImports()->count())->toBe(1)
        ->and($this->upload->uploadedRows()->count())->toBe(1)
        ->and($this->campaign->onceOffContacts()->count())->toBe(1)
        ->and(Storage::disk('local')->allFiles())->toBe($files);
})->with('non-draft campaign statuses');

test('non-draft campaigns reject all selected and individual syncs in every upload mode without writes', function (CampaignStatus $status, ContactUploadMode $mode) {
    $this->upload->update(['mode' => $mode]);
    $plan = app(CampaignContactSyncPlan::class)->make($this->campaign, $this->upload);
    $this->campaign->update(['status' => $status]);
    $row = $this->upload->uploadedRows()->sole();
    $beforeRow = $row->getAttributes();
    $beforeUpload = $this->upload->fresh()->getAttributes();
    $beforeContact = $this->contact->fresh()->getAttributes();
    foreach ([['fingerprint' => $plan['fingerprint']], ['row_ids' => [$row->public_id]]] as $payload) {
        $this->postJson(route('campaigns.once-off.contact-imports.sync', [$this->campaign, $this->upload]), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('sync');
    }
    expect($row->fresh()->getAttributes())->toBe($beforeRow)
        ->and($this->upload->fresh()->getAttributes())->toBe($beforeUpload)
        ->and($this->contact->fresh()->getAttributes())->toBe($beforeContact)
        ->and($this->campaign->onceOffContacts()->count())->toBe(1);
})->with('non-draft campaign statuses')->with(ContactUploadMode::cases());

test('write services recheck persisted campaign status when supplied a stale draft model', function (string $action) {
    Campaign::query()->whereKey($this->campaign->id)->update(['status' => CampaignStatus::Launched]);
    $files = Storage::disk('local')->allFiles();
    expect($this->campaign->status)->toBe(CampaignStatus::Draft);
    expect(fn () => match ($action) {
        'schedule' => app(SaveOnceOffCampaignSchedules::class)->handle($this->campaign, [$this->attempt]),
        'upload' => app(StageCampaignContactUpload::class)->handle($this->campaign, $this->editor, ContactUploadMode::Append, [], draftEditingFile()),
        'sync' => app(SyncOnceOffCampaignContactImport::class)->handle($this->campaign, $this->upload),
    })->toThrow(ValidationException::class);
    expect($this->campaign->onceOffSchedules()->sole()->public_id)->toBe($this->schedule->public_id)
        ->and($this->campaign->contactImports()->count())->toBe(1)
        ->and($this->campaign->onceOffContacts()->count())->toBe(1)
        ->and(Storage::disk('local')->allFiles())->toBe($files);
})->with(['schedule', 'upload', 'sync']);

test('stopping a launched campaign restores schedule upload and sync editing', function () {
    $this->post(route('campaigns.once-off.launch', $this->campaign))->assertSessionHasNoErrors();
    $this->post(route('campaigns.once-off.stop', $this->campaign))->assertSessionHasNoErrors();
    expect($this->campaign->fresh()->status)->toBe(CampaignStatus::Draft);
    $this->put(route('campaigns.once-off.schedule.update', $this->campaign), ['schedules' => [$this->attempt]])
        ->assertSessionHasNoErrors();
    $this->post(route('campaigns.once-off.contact-imports.store', $this->campaign), [
        'file' => draftEditingFile(), 'mode' => ContactUploadMode::Append->value,
    ])->assertSessionHasNoErrors();
    $this->post(route('campaigns.once-off.contacts.store', $this->campaign), [
        'first_name' => 'Manual', 'number' => '+14155550103', 'mode' => ContactUploadMode::Append->value,
    ])->assertSessionHasNoErrors();
    $this->post(route('campaigns.once-off.contact-imports.sync', [$this->campaign, $this->upload]))
        ->assertSessionHasNoErrors();
    expect($this->campaign->contactImports()->count())->toBe(3)
        ->and($this->campaign->onceOffContacts()->count())->toBe(2);
});
