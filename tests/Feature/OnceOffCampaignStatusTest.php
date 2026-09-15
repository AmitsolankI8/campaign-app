<?php

use App\Enums\CampaignStatus;
use App\Enums\CampaignType;
use App\Enums\ContactUploadMode;
use App\Models\Campaign;
use App\Models\Campaigns\OnceOffCampaign;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\ChangeOnceOffCampaignStatus;
use App\Support\CommunicationRegistry;
use Database\Seeders\CommunicationSeeder;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(now()->utc()->setDate(2030, 1, 15)->setTime(9, 0));
    $this->seed(CommunicationSeeder::class);
    foreach (['campaigns.view', 'campaigns.edit'] as $permission) {
        Permission::factory()->fromRegistry($permission)->create();
    }
    $this->editor = User::factory()->create();
    $this->editor->givePermissionTo(['campaigns.view', 'campaigns.edit']);
    $this->actingAs($this->editor);
    $this->campaign = OnceOffCampaign::factory()->create();
    $this->schedule = $this->campaign->schedules()->create([
        'attempt_count' => 1,
        'scheduled_at' => now()->addHour(),
        'channel' => array_key_first(CommunicationRegistry::channels()),
    ]);
    $this->contact = $this->campaign->contacts()->create([
        'first_name' => 'Alice', 'number' => '+14155550101',
    ]);
});

test('status endpoints require authentication', function (string $action) {
    $this->app['auth']->forgetGuards();
    $this->post(route('campaigns.once-off.'.$action, $this->campaign))->assertRedirect(route('login'));
    expect($this->campaign->fresh()->status)->toBe(CampaignStatus::Draft);
})->with(['launch', 'stop']);

test('status endpoints require both direct or inherited permissions', function (string $action, array $permissions, bool $inherited) {
    $status = $action === 'launch' ? CampaignStatus::Draft : CampaignStatus::Launched;
    $this->campaign->update(['status' => $status]);
    $user = User::factory()->create();
    if ($inherited) {
        $role = Role::factory()->create();
        $role->givePermissionTo($permissions);
        $user->assignRole($role);
    } else {
        $user->givePermissionTo($permissions);
    }
    $this->actingAs($user)->postJson(route('campaigns.once-off.'.$action, $this->campaign))->assertForbidden();
    expect($this->campaign->fresh()->status)->toBe($status);
})->with(['launch', 'stop'])->with([[[]], [['campaigns.view']], [['campaigns.edit']]])->with([false, true]);

test('authorized editors can launch stop and relaunch using saved contacts and schedules', function (bool $inherited) {
    if ($inherited) {
        $user = User::factory()->create();
        $role = Role::factory()->create();
        $role->givePermissionTo(['campaigns.view', 'campaigns.edit']);
        $user->assignRole($role);
        $this->actingAs($user);
    }
    $url = route('campaigns.once-off.schedule.show', $this->campaign);
    $schedule = $this->schedule->fresh()->getAttributes();
    $contact = $this->contact->fresh()->getAttributes();
    foreach (['launch' => CampaignStatus::Launched, 'stop' => CampaignStatus::Draft] as $action => $status) {
        $this->from($url)->post(route('campaigns.once-off.'.$action, $this->campaign), ['status' => CampaignStatus::Cancelled->value])
            ->assertRedirect($url)->assertSessionHasNoErrors();
        expect($this->campaign->fresh()->status)->toBe($status);
        $this->get($url)->assertInertia(fn (Assert $page) => $page
            ->where('campaign.id', $this->campaign->public_id)
            ->where('campaign.status', $status->toArray())
            ->where('campaign.scheduled_at', $this->schedule->scheduled_at->toJSON())
            ->where('auth.permissions', ['campaigns.view', 'campaigns.edit']));
    }
    $this->post(route('campaigns.once-off.launch', $this->campaign))->assertSessionHasNoErrors();
    expect($this->campaign->fresh()->status)->toBe(CampaignStatus::Launched)
        ->and($this->schedule->fresh()->getAttributes())->toBe($schedule)
        ->and($this->contact->fresh()->getAttributes())->toBe($contact);
})->with([false, true]);

test('launch alerts identify every missing requirement without changing status', function (bool $schedule, bool $contact, array $errors) {
    if (! $schedule) {
        $this->schedule->delete();
    }
    if (! $contact) {
        $this->contact->delete();
    }
    $this->from(route('campaigns.once-off.show', $this->campaign))
        ->post(route('campaigns.once-off.launch', $this->campaign))
        ->assertSessionHasErrors($errors);
    expect($this->campaign->fresh()->status)->toBe(CampaignStatus::Draft);
})->with([
    [false, true, ['schedule' => 'Set a schedule before launching the campaign.']],
    [true, false, ['contacts' => 'Add and sync at least one contact before launching the campaign.']],
    [false, false, ['schedule', 'contacts']],
]);

test('staged uploads and other campaign contacts do not qualify for launch', function () {
    $this->contact->delete();
    $other = OnceOffCampaign::factory()->create();
    $other->contacts()->create(['first_name' => 'Bob', 'number' => '+14155550102']);
    $this->post(route('campaigns.once-off.contacts.store', $this->campaign), [
        'first_name' => 'Charlie', 'number' => '+14155550103', 'mode' => ContactUploadMode::Append->value,
    ])->assertSessionHasNoErrors();
    expect($this->campaign->contactImports()->count())->toBe(1);
    $this->postJson(route('campaigns.once-off.launch', $this->campaign))
        ->assertUnprocessable()->assertJsonValidationErrors('contacts');
    expect($this->campaign->fresh()->status)->toBe(CampaignStatus::Draft);
});

test('launch requires this campaigns first attempt', function () {
    $this->schedule->update(['attempt_count' => 2]);
    $other = OnceOffCampaign::factory()->create();
    $other->schedules()->create([
        'attempt_count' => 1, 'scheduled_at' => now()->addHour(),
        'channel' => array_key_first(CommunicationRegistry::channels()),
    ]);
    $this->postJson(route('campaigns.once-off.launch', $this->campaign))
        ->assertUnprocessable()->assertJsonValidationErrors('schedule');
    expect($this->campaign->fresh()->status)->toBe(CampaignStatus::Draft);
});

test('stop is rejected at and after the first schedule even with future follow ups', function (int $seconds) {
    $this->campaign->update(['status' => CampaignStatus::Launched]);
    $this->campaign->schedules()->create([
        'attempt_count' => 2, 'scheduled_at' => now()->addDay(),
        'channel' => array_key_first(CommunicationRegistry::channels()),
    ]);
    $this->travelTo($this->schedule->scheduled_at->copy()->addSeconds($seconds));
    $this->postJson(route('campaigns.once-off.stop', $this->campaign))
        ->assertUnprocessable()->assertJsonValidationErrors('status');
    expect($this->campaign->fresh()->status)->toBe(CampaignStatus::Launched);
})->with([0, 1, 3600]);

test('stop succeeds one second before the first schedule', function () {
    $this->campaign->update(['status' => CampaignStatus::Launched]);
    $this->travelTo($this->schedule->scheduled_at->copy()->subSecond());
    $this->post(route('campaigns.once-off.stop', $this->campaign))->assertSessionHasNoErrors();
    expect($this->campaign->fresh()->status)->toBe(CampaignStatus::Draft);
});

test('stop fails closed without a first schedule', function () {
    $this->campaign->update(['status' => CampaignStatus::Launched]);
    $this->schedule->delete();
    $this->postJson(route('campaigns.once-off.stop', $this->campaign))
        ->assertUnprocessable()->assertJsonValidationErrors('status');
    expect($this->campaign->fresh()->status)->toBe(CampaignStatus::Launched);
});

test('invalid and repeated status transitions do not change campaigns', function (string $action, CampaignStatus $status) {
    $this->campaign->update(['status' => $status]);
    $this->postJson(route('campaigns.once-off.'.$action, $this->campaign))
        ->assertUnprocessable()->assertJsonValidationErrors('status');
    expect($this->campaign->fresh()->status)->toBe($status);
})->with([
    ['launch', CampaignStatus::Launched], ['launch', CampaignStatus::Running],
    ['launch', CampaignStatus::Paused], ['launch', CampaignStatus::Cancelled],
    ['stop', CampaignStatus::Draft], ['stop', CampaignStatus::Running],
    ['stop', CampaignStatus::Paused], ['stop', CampaignStatus::Cancelled],
]);

test('status routes reject other campaign types and internal or unknown ids', function (string $action) {
    foreach ([CampaignType::Ongoing, CampaignType::BatchProcessing] as $type) {
        $other = Campaign::factory()->create(['campaign_type' => $type]);
        $this->postJson(route('campaigns.once-off.'.$action, $other))->assertNotFound();
        expect($other->fresh()->status)->toBe(CampaignStatus::Draft);
    }
    foreach ([$this->campaign->id, (string) Str::ulid()] as $id) {
        $this->postJson(route('campaigns.once-off.'.$action, $id))->assertNotFound();
    }
})->with(['launch', 'stop']);

test('status changes recheck persisted state instead of trusting a stale model', function (string $action) {
    $this->campaign->load('firstOnceOffSchedule');
    if ($action === 'launch') {
        Campaign::query()->whereKey($this->campaign->id)->update(['status' => CampaignStatus::Running]);
    } else {
        $this->campaign->update(['status' => CampaignStatus::Launched]);
        $this->schedule->update(['scheduled_at' => now()]);
    }
    expect(fn () => app(ChangeOnceOffCampaignStatus::class)->{$action}($this->campaign))
        ->toThrow(ValidationException::class);
    expect($this->campaign->fresh()->status)->toBe($action === 'launch' ? CampaignStatus::Running : CampaignStatus::Launched);
})->with(['launch', 'stop']);
