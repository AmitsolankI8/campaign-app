<?php

use App\Enums\CampaignType;
use App\Enums\ContactUploadMode;
use App\Models\Campaign;
use App\Models\Campaigns\OnceOffCampaign;
use App\Models\CommunicationProvider;
use App\Models\OnceOffCampaignSchedule;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\CommunicationRegistry;
use App\Support\SaveOnceOffCampaignSchedules;
use Database\Seeders\CommunicationSeeder;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(CommunicationSeeder::class);
    foreach (['campaigns.view', 'campaigns.edit'] as $permission) {
        Permission::factory()->fromRegistry($permission)->create();
    }
    $this->editor = User::factory()->create();
    $this->editor->givePermissionTo(['campaigns.view', 'campaigns.edit']);
    $this->actingAs($this->editor);
    $this->campaign = OnceOffCampaign::factory()->create();
});

function scheduleAttempt(array $overrides = []): array
{
    return ['scheduled_at' => '2030-01-15T09:30:00.000Z', 'channel' => array_key_first(CommunicationRegistry::channels()), ...$overrides];
}

function savedScheduleAttempt(OnceOffCampaign $campaign, int $number = 1): OnceOffCampaignSchedule
{
    return $campaign->schedules()->create([
        ...scheduleAttempt(['scheduled_at' => sprintf('2030-01-%02dT09:30:00.000Z', 14 + $number)]),
        'attempt_count' => $number,
    ]);
}

test('schedule endpoints require authentication', function () {
    $this->app['auth']->forgetGuards();
    $this->get(route('campaigns.once-off.schedule.show', $this->campaign))->assertRedirect(route('login'));
    $this->put(route('campaigns.once-off.schedule.update', $this->campaign), ['schedules' => [scheduleAttempt()]])
        ->assertRedirect(route('login'));
    expect(OnceOffCampaignSchedule::count())->toBe(0);
});

test('schedule permissions deny missing grants without changing saved attempts', function (array $grants, bool $inherited) {
    $saved = savedScheduleAttempt($this->campaign);
    $before = $saved->fresh()->getAttributes();
    $user = User::factory()->create();
    if ($inherited) {
        $role = Role::factory()->create();
        $role->givePermissionTo($grants);
        $user->assignRole($role);
    } else {
        $user->givePermissionTo($grants);
    }
    $response = $this->actingAs($user)->get(route('campaigns.once-off.schedule.show', $this->campaign));
    if (in_array('campaigns.view', $grants, true)) {
        $response->assertSuccessful()->assertInertia(fn (Assert $page) => $page
            ->where('auth.permissions', ['campaigns.view'])
            ->where('schedules.0.id', $saved->public_id));
    } else {
        $response->assertForbidden();
    }
    $this->putJson(route('campaigns.once-off.schedule.update', $this->campaign), ['schedules' => [scheduleAttempt()]])
        ->assertForbidden();
    expect($saved->fresh()->getAttributes())->toBe($before)
        ->and(OnceOffCampaignSchedule::count())->toBe(1);
})->with(['none' => [[]], 'view only' => [['campaigns.view']], 'edit only' => [['campaigns.edit']]])
    ->with(['direct' => false, 'inherited' => true]);

test('direct inherited and mixed permissions grant schedule editing', function (string $source) {
    $user = User::factory()->create();
    if ($source === 'direct') {
        $user->givePermissionTo(['campaigns.view', 'campaigns.edit']);
    } else {
        $role = Role::factory()->create();
        $role->givePermissionTo($source === 'inherited' ? ['campaigns.view', 'campaigns.edit'] : ['campaigns.edit']);
        $user->assignRole($role);
        if ($source === 'mixed') {
            $user->givePermissionTo('campaigns.view');
        }
    }
    $this->actingAs($user)->put(route('campaigns.once-off.schedule.update', $this->campaign), ['schedules' => [scheduleAttempt()]])
        ->assertSessionHasNoErrors()->assertRedirect(route('campaigns.once-off.schedule.show', $this->campaign));
    $this->get(route('campaigns.once-off.schedule.show', $this->campaign))->assertInertia(fn (Assert $page) => $page
        ->component('campaigns/once-off/Schedule')
        ->where('auth.permissions', ['campaigns.view', 'campaigns.edit'])
        ->has('schedules', 1));
})->with(['direct', 'inherited', 'mixed']);

test('unscheduled campaigns return empty schedules without creating defaults', function () {
    $this->get(route('campaigns.once-off.schedule.show', $this->campaign))->assertInertia(fn (Assert $page) => $page
        ->component('campaigns/once-off/Schedule')
        ->where('campaign.id', $this->campaign->public_id)
        ->where('campaign.scheduled_at', null)
        ->where('schedules', [])
        ->has('channels', count(CommunicationRegistry::channels()))
        ->missing('contacts')->missing('contactImports')->missing('contactSummary'));
    expect(OnceOffCampaignSchedule::count())->toBe(0);
});

test('schedule channels use seeded database metadata without exposing provider secrets', function () {
    $key = array_key_first(CommunicationRegistry::channels());
    CommunicationProvider::query()->where('channel', $key)->update(['channel_name' => 'Renamed channel']);
    $provider = CommunicationProvider::query()->where('channel', $key)->firstOrFail();
    $provider->update(['credentials' => ['secret' => 'schedule-must-not-expose-this']]);
    $expected = collect(CommunicationRegistry::channels())->map(fn (array $definition, string $channel) => [
        'value' => $channel, 'label' => $channel === $key ? 'Renamed channel' : $definition['name'],
    ])->values()->all();

    $this->get(route('campaigns.once-off.schedule.show', $this->campaign))->assertInertia(fn (Assert $page) => $page
        ->where('channels', $expected)->missing('providers')->missing('credentials'));
});

test('every seeded communication channel can be used without choosing a provider', function (string $channel) {
    $this->put(route('campaigns.once-off.schedule.update', $this->campaign), ['schedules' => [scheduleAttempt(['channel' => $channel])]])
        ->assertSessionHasNoErrors();
    expect($this->campaign->schedules()->sole()->channel)->toBe($channel);
})->with(fn () => array_keys(CommunicationRegistry::channels()));

test('schedule validation reads current database channels instead of hardcoded registry keys', function () {
    $previous = array_key_first(CommunicationRegistry::channels());
    CommunicationProvider::query()->where('channel', $previous)->update(['channel' => 'database_channel']);
    $this->putJson(route('campaigns.once-off.schedule.update', $this->campaign), ['schedules' => [scheduleAttempt(['channel' => $previous])]])
        ->assertUnprocessable()->assertJsonValidationErrors('schedules.0.channel');
    $this->put(route('campaigns.once-off.schedule.update', $this->campaign), ['schedules' => [scheduleAttempt(['channel' => 'database_channel'])]])
        ->assertSessionHasNoErrors();
    expect($this->campaign->schedules()->sole()->channel)->toBe('database_channel');
});

test('schedule routes reject other campaign types and unknown public identifiers', function (CampaignType $type) {
    $other = Campaign::factory()->create(['campaign_type' => $type]);
    $this->get(route('campaigns.once-off.schedule.show', $other))->assertNotFound();
    $this->putJson(route('campaigns.once-off.schedule.update', $other), ['schedules' => [scheduleAttempt()]])->assertNotFound();
    $missing = (string) Str::ulid();
    $this->get(route('campaigns.once-off.schedule.show', $missing))->assertNotFound();
    $this->putJson(route('campaigns.once-off.schedule.update', $missing), ['schedules' => [scheduleAttempt()]])->assertNotFound();
    $this->get(route('campaigns.once-off.schedule.show', $this->campaign->id))->assertNotFound();
    expect(OnceOffCampaignSchedule::count())->toBe(0);
})->with([CampaignType::Ongoing, CampaignType::BatchProcessing]);

test('saving multiple attempts assigns contiguous counts and exposes UTC public resources', function () {
    $before = $this->campaign->fresh()->getAttributes();
    $attempts = [scheduleAttempt(), scheduleAttempt(['scheduled_at' => '2030-01-16T09:30:00.000Z']), scheduleAttempt(['scheduled_at' => '2030-01-17T09:30:00.000Z'])];
    $this->put(route('campaigns.once-off.schedule.update', $this->campaign), ['schedules' => $attempts])
        ->assertSessionHasNoErrors()->assertRedirect(route('campaigns.once-off.schedule.show', $this->campaign));
    $saved = $this->campaign->schedules()->orderBy('attempt_count')->get();
    expect($saved->pluck('attempt_count')->all())->toBe([1, 2, 3])
        ->and($this->campaign->fresh()->getAttributes())->toBe($before);
    foreach ($saved as $index => $schedule) {
        expect(Str::isUlid($schedule->public_id))->toBeTrue()
            ->and($schedule->scheduled_at->format('Y-m-d\TH:i:s.v\Z'))->toBe($attempts[$index]['scheduled_at']);
    }
    $this->get(route('campaigns.once-off.schedule.show', $this->campaign))->assertInertia(function (Assert $page) use ($saved) {
        $page->where('campaign.scheduled_at', $saved[0]->scheduled_at->toJSON())->has('schedules', 3);
        foreach ($saved as $index => $schedule) {
            $page->where("schedules.{$index}", [
                'id' => $schedule->public_id, 'attempt_count' => $index + 1,
                'scheduled_at' => $schedule->scheduled_at->toJSON(), 'channel' => $schedule->channel,
            ]);
        }
    });
});

test('resaving unchanged attempts preserves public IDs and attempt numbers', function () {
    $first = savedScheduleAttempt($this->campaign);
    $second = savedScheduleAttempt($this->campaign, 2);
    $payload = ['schedules' => [scheduleAttempt(['id' => $first->public_id]), scheduleAttempt(['id' => $second->public_id, 'scheduled_at' => '2030-01-16T09:30:00.000Z'])]];
    for ($iteration = 0; $iteration < 2; $iteration++) {
        $this->put(route('campaigns.once-off.schedule.update', $this->campaign), $payload)->assertSessionHasNoErrors();
    }
    expect($this->campaign->schedules()->orderBy('attempt_count')->pluck('public_id')->all())
        ->toBe([$first->public_id, $second->public_id])
        ->and($first->fresh()->attempt_count)->toBe(1)->and($second->fresh()->attempt_count)->toBe(2);
});

test('removing a middle follow-up and appending one retains IDs and renumbers attempts', function () {
    $first = savedScheduleAttempt($this->campaign);
    $removed = savedScheduleAttempt($this->campaign, 2);
    $third = savedScheduleAttempt($this->campaign, 3);
    $other = savedScheduleAttempt(OnceOffCampaign::factory()->create());
    $otherBefore = $other->fresh()->getAttributes();
    $this->put(route('campaigns.once-off.schedule.update', $this->campaign), ['schedules' => [
        scheduleAttempt(['id' => $first->public_id, 'scheduled_at' => '2030-01-15T10:00:00.000Z']),
        scheduleAttempt(['id' => $third->public_id, 'scheduled_at' => '2030-01-17T09:30:00.000Z']),
        scheduleAttempt(['id' => null, 'scheduled_at' => '2030-01-18T09:30:00.000Z']),
    ]])->assertSessionHasNoErrors();
    $saved = $this->campaign->schedules()->orderBy('attempt_count')->get();
    $this->assertModelMissing($removed);
    expect($saved->pluck('attempt_count')->all())->toBe([1, 2, 3])
        ->and($saved[0]->public_id)->toBe($first->public_id)
        ->and($saved[1]->public_id)->toBe($third->public_id)
        ->and($saved[2]->public_id)->not->toBeIn([$first->public_id, $removed->public_id, $third->public_id])
        ->and($first->fresh()->scheduled_at->format('H:i'))->toBe('10:00')
        ->and($other->fresh()->getAttributes())->toBe($otherBefore);
});

test('all follow-ups can be removed while retaining the required first attempt', function () {
    $first = savedScheduleAttempt($this->campaign);
    savedScheduleAttempt($this->campaign, 2);
    $this->put(route('campaigns.once-off.schedule.update', $this->campaign), ['schedules' => [scheduleAttempt(['id' => $first->public_id])]])
        ->assertSessionHasNoErrors();
    expect($this->campaign->schedules()->sole()->public_id)->toBe($first->public_id);
});

test('invalid schedule structures and values leave existing attempts untouched', function (array $payload, string $field) {
    $saved = savedScheduleAttempt($this->campaign);
    $before = $saved->fresh()->getAttributes();
    $this->putJson(route('campaigns.once-off.schedule.update', $this->campaign), $payload)
        ->assertUnprocessable()->assertJsonValidationErrors($field);
    expect($saved->fresh()->getAttributes())->toBe($before)->and(OnceOffCampaignSchedule::count())->toBe(1);
})->with([
    'missing list' => [[], 'schedules'],
    'empty list' => [['schedules' => []], 'schedules'],
    'null list' => [['schedules' => null], 'schedules'],
    'non-list' => [['schedules' => ['first' => scheduleAttempt()]], 'schedules'],
    'string row' => [['schedules' => ['invalid']], 'schedules.0'],
    'blank first date' => [['schedules' => [scheduleAttempt(['scheduled_at' => ''])]], 'schedules.0.scheduled_at'],
    'blank first channel' => [['schedules' => [scheduleAttempt(['channel' => ''])]], 'schedules.0.channel'],
    'unknown channel' => [['schedules' => [scheduleAttempt(['channel' => 'unknown'])]], 'schedules.0.channel'],
    'invalid date' => [['schedules' => [scheduleAttempt(['scheduled_at' => '2030-02-30T09:30:00.000Z'])]], 'schedules.0.scheduled_at'],
    'local date without timezone' => [['schedules' => [scheduleAttempt(['scheduled_at' => '2030-01-15T09:30'])]], 'schedules.0.scheduled_at'],
    'non UTC input' => [['schedules' => [scheduleAttempt(['scheduled_at' => '2030-01-15T15:00:00.000+05:30'])]], 'schedules.0.scheduled_at'],
    'blank follow-up date' => [['schedules' => [scheduleAttempt(), scheduleAttempt(['scheduled_at' => ''])]], 'schedules.1.scheduled_at'],
    'blank follow-up channel' => [['schedules' => [scheduleAttempt(), scheduleAttempt(['channel' => '', 'scheduled_at' => '2030-01-16T09:30:00.000Z'])]], 'schedules.1.channel'],
    'client attempt number' => [['schedules' => [scheduleAttempt(['attempt_count' => 99])]], 'schedules.0'],
    'client campaign ID' => [['schedules' => [scheduleAttempt(['campaign_id' => 99])]], 'schedules.0'],
]);

test('every follow-up must be strictly later than its immediately preceding attempt', function (string $lastDate) {
    $saved = savedScheduleAttempt($this->campaign);
    $this->putJson(route('campaigns.once-off.schedule.update', $this->campaign), ['schedules' => [
        scheduleAttempt(['id' => $saved->public_id]),
        scheduleAttempt(['scheduled_at' => '2030-01-17T09:30:00.000Z']),
        scheduleAttempt(['scheduled_at' => $lastDate]),
    ]])->assertUnprocessable()->assertJsonValidationErrors('schedules.2.scheduled_at');
    expect($this->campaign->schedules()->sole()->public_id)->toBe($saved->public_id);
})->with(['same as previous' => '2030-01-17T09:30:00.000Z', 'after first but before previous' => '2030-01-16T09:30:00.000Z', 'before first' => '2030-01-14T09:30:00.000Z']);

test('follow-up dates can be edited to less than the default twenty-four-hour gap', function () {
    $this->put(route('campaigns.once-off.schedule.update', $this->campaign), ['schedules' => [
        scheduleAttempt(), scheduleAttempt(['scheduled_at' => '2030-01-15T09:30:01.000Z']),
    ]])->assertSessionHasNoErrors();
    expect($this->campaign->schedules()->count())->toBe(2);
});

test('foreign missing numeric and duplicated schedule IDs are rejected without writes', function (string $kind) {
    $saved = savedScheduleAttempt($this->campaign);
    $foreign = savedScheduleAttempt(OnceOffCampaign::factory()->create());
    $before = OnceOffCampaignSchedule::query()->orderBy('id')->get()->map->getAttributes()->all();
    $id = match ($kind) {
        'foreign' => $foreign->public_id,
        'missing' => (string) Str::ulid(),
        'numeric' => $saved->id,
        'duplicate' => $saved->public_id,
    };
    $this->putJson(route('campaigns.once-off.schedule.update', $this->campaign), ['schedules' => [
        scheduleAttempt(['id' => $saved->public_id]),
        scheduleAttempt(['id' => $id, 'scheduled_at' => '2030-01-16T09:30:00.000Z']),
    ]])->assertUnprocessable()->assertJsonValidationErrors('schedules.1.id');
    expect(OnceOffCampaignSchedule::query()->orderBy('id')->get()->map->getAttributes()->all())->toBe($before);
})->with(['foreign', 'missing', 'numeric', 'duplicate']);

test('transaction failure restores deleted and edited attempts and their original numbers', function () {
    $first = savedScheduleAttempt($this->campaign);
    savedScheduleAttempt($this->campaign, 2);
    $before = $this->campaign->schedules()->orderBy('id')->get()->map->getAttributes()->all();
    $event = 'eloquent.creating: '.OnceOffCampaignSchedule::class;
    Event::listen($event, function () {
        throw new RuntimeException('Simulated schedule storage failure');
    });
    try {
        expect(fn () => app(SaveOnceOffCampaignSchedules::class)->handle($this->campaign, [
            scheduleAttempt(['id' => $first->public_id, 'scheduled_at' => '2030-01-15T10:30:00.000Z']),
            scheduleAttempt(['scheduled_at' => '2030-01-17T09:30:00.000Z']),
        ]))->toThrow(RuntimeException::class, 'Simulated schedule storage failure');
    } finally {
        Event::forget($event);
    }
    expect($this->campaign->schedules()->orderBy('id')->get()->map->getAttributes()->all())->toBe($before);
});

test('save action rechecks stale IDs after request validation before removing any rows', function () {
    $saved = savedScheduleAttempt($this->campaign);
    $before = $saved->fresh()->getAttributes();
    expect(fn () => app(SaveOnceOffCampaignSchedules::class)->handle($this->campaign, [scheduleAttempt(['id' => (string) Str::ulid()])]))
        ->toThrow(ValidationException::class);
    expect($saved->fresh()->getAttributes())->toBe($before);
});

test('basic details expose only the saved first attempt date across campaign sections', function (string $routeName, bool $hasSchedule) {
    $first = null;
    if ($hasSchedule) {
        // Insert the follow-up first to prove the sidebar uses attempt 1, not row insertion order.
        savedScheduleAttempt($this->campaign, 2);
        $first = savedScheduleAttempt($this->campaign);
    }
    $this->get(route($routeName, $this->campaign))->assertInertia(function (Assert $page) use ($first, $routeName) {
        $page->where('campaign.scheduled_at', $first?->scheduled_at->toJSON());
        if ($routeName !== 'campaigns.once-off.schedule.show') {
            $page->missing('schedules')->missing('channels');
        }
    });
})->with(['campaigns.once-off.show', 'campaigns.once-off.contacts.index', 'campaigns.once-off.contact-imports.index', 'campaigns.once-off.schedule.show', 'campaigns.edit'])
    ->with(['unscheduled' => false, 'scheduled' => true]);

test('nested upload details include the first saved schedule date', function () {
    $first = savedScheduleAttempt($this->campaign);
    $this->post(route('campaigns.once-off.contacts.store', $this->campaign), [
        'first_name' => 'Alice', 'number' => '+14155550101', 'mode' => ContactUploadMode::Append->value,
    ])->assertSessionHasNoErrors();
    $upload = $this->campaign->contactImports()->sole();
    $this->get(route('campaigns.once-off.contact-imports.show', [$this->campaign, $upload]))
        ->assertInertia(fn (Assert $page) => $page->component('campaigns/once-off/UploadShow')
            ->where('campaign.scheduled_at', $first->scheduled_at->toJSON())->missing('schedules'));
});

test('updating the first attempt refreshes the basic details date', function () {
    $first = savedScheduleAttempt($this->campaign);
    $this->put(route('campaigns.once-off.schedule.update', $this->campaign), ['schedules' => [
        scheduleAttempt(['id' => $first->public_id, 'scheduled_at' => '2030-01-20T18:15:00.000Z']),
    ]])->assertSessionHasNoErrors();
    $this->get(route('campaigns.once-off.show', $this->campaign))->assertInertia(fn (Assert $page) => $page
        ->where('campaign.scheduled_at', '2030-01-20T18:15:00.000000Z'));
});

test('blank first and follow-up attempts report all required fields without creating records', function () {
    $this->putJson(route('campaigns.once-off.schedule.update', $this->campaign), ['schedules' => [
        ['id' => null, 'scheduled_at' => null, 'channel' => ''],
        ['id' => null, 'scheduled_at' => null, 'channel' => ''],
    ]])->assertUnprocessable()->assertJsonValidationErrors([
        'schedules.0.scheduled_at', 'schedules.0.channel',
        'schedules.1.scheduled_at', 'schedules.1.channel',
    ]);
    expect(OnceOffCampaignSchedule::count())->toBe(0);
});

test('save action refuses an empty schedule without deleting the first attempt', function () {
    $saved = savedScheduleAttempt($this->campaign);
    expect(fn () => app(SaveOnceOffCampaignSchedules::class)->handle($this->campaign, []))
        ->toThrow(ValidationException::class);
    expect($this->campaign->schedules()->sole()->public_id)->toBe($saved->public_id);
});
