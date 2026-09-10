<?php

use App\Models\CommunicationProvider;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\CommunicationRegistry;
use App\Support\PermissionRegistry;
use Database\Seeders\CommunicationSeeder;
use Database\Seeders\UserManagementSeeder;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(CommunicationSeeder::class);
    Permission::factory()->fromRegistry('communication.view')->create();
    Permission::factory()->fromRegistry('communication.edit')->create();
});

function communicationPayload(): array
{
    return ['is_active' => true, 'priority' => 2, 'credentials' => [
        'account_sid' => 'AC-example', 'auth_token' => 'secret-token', 'from' => '+15551234567',
    ]];
}

test('communication settings require authentication', function () {
    $this->get(route('communication.index'))->assertRedirect(route('login'));
    $this->put(route('communication.update', ['sms', 'twilio']), [])->assertRedirect(route('login'));
});

test('communication permissions deny missing or partial grants', function (array $grants, bool $canView) {
    $user = User::factory()->create();
    $user->givePermissionTo($grants);
    $response = $this->actingAs($user)->get(route('communication.index'));
    if ($canView) {
        $response->assertSuccessful()->assertInertia(fn (Assert $page) => $page->component('communication/Index')->has('channels', count(CommunicationRegistry::channels())));
    } else {
        $response->assertForbidden();
    }
    $this->put(route('communication.update', ['sms', 'twilio']), communicationPayload())->assertForbidden();
    expect(CommunicationProvider::count())->toBe(count(CommunicationRegistry::providers()))
        ->and(CommunicationProvider::where('is_active', true)->exists())->toBeFalse();
})->with([[[], false], [['communication.view'], true], [['communication.edit'], false]]);

test('direct and inherited grants allow encrypted updates without exposing secrets', function (bool $inherited) {
    $user = User::factory()->create();
    $role = Role::factory()->create();
    $role->givePermissionTo(['communication.view', 'communication.edit']);
    if ($inherited) {
        $user->assignRole($role);
    } else {
        $user->givePermissionTo(['communication.view', 'communication.edit']);
    }
    $this->actingAs($user)->put(route('communication.update', ['sms', 'twilio']), communicationPayload())
        ->assertSessionHasNoErrors()->assertRedirect(route('communication.index'));
    $provider = CommunicationProvider::where('channel', 'sms')->where('provider', 'twilio')->firstOrFail();
    expect($provider->credentials['auth_token'])->toBe('secret-token')
        ->and($provider->getRawOriginal('credentials'))->not->toContain('secret-token')
        ->and($provider->toArray())->not->toHaveKey('credentials');
    $this->get(route('communication.index'))->assertInertia(fn (Assert $page) => $page
        ->where('auth.permissions', ['communication.view', 'communication.edit'])
        ->where('channels', function ($channels) use ($provider) {
            $sms = collect($channels)->firstWhere('key', 'sms');
            $row = collect($sms['providers'])->firstWhere('provider', 'twilio');
            expect($row['id'])->toBe($provider->public_id)
                ->and($row['credentials']['auth_token'])->toBe('')
                ->and($row['saved_secrets'])->toBe(['auth_token']);

            return true;
        }));

    $payload = communicationPayload();
    $payload['credentials']['auth_token'] = '';
    $payload['priority'] = 1;
    $this->put(route('communication.update', ['sms', 'twilio']), $payload)->assertSessionHasNoErrors();
    expect($provider->fresh()->credentials['auth_token'])->toBe('secret-token')
        ->and($provider->fresh()->priority)->toBe(1);
    $payload['credentials']['auth_token'] = 'replacement-token';
    $payload['is_active'] = false;
    $this->put(route('communication.update', ['sms', 'twilio']), $payload)->assertSessionHasNoErrors();
    expect($provider->fresh()->credentials['auth_token'])->toBe('replacement-token')
        ->and($provider->fresh()->is_active)->toBeFalse();
    if ($inherited) {
        $role->revokePermissionTo('communication.edit');
        $user->unsetRelation('roles')->unsetRelation('permissions');
        $this->put(route('communication.update', ['sms', 'twilio']), $payload)->assertForbidden();
    }
})->with([false, true]);

test('invalid settings do not persist or flash credentials', function (array $override, string $error) {
    $user = User::factory()->create();
    $user->givePermissionTo(['communication.view', 'communication.edit']);
    $this->actingAs($user)->from(route('communication.index'))
        ->put(route('communication.update', ['sms', 'twilio']), array_replace_recursive(communicationPayload(), $override))
        ->assertSessionHasErrors($error)->assertSessionMissing('_old_input.credentials');
    expect(CommunicationProvider::count())->toBe(count(CommunicationRegistry::providers()))
        ->and(CommunicationProvider::where('is_active', true)->exists())->toBeFalse();
})->with([
    [['priority' => 0], 'priority'],
    [['priority' => 1.5], 'priority'],
    [['is_active' => 'yes'], 'is_active'],
    [['credentials' => ['auth_token' => '']], 'credentials.auth_token'],
    [['credentials' => ['injected_key' => 'value']], 'credentials'],
]);

test('each supported provider validates its required fields and can save a draft', function (string $channel, string $provider) {
    $user = User::factory()->create();
    $user->givePermissionTo(['communication.view', 'communication.edit']);
    $record = CommunicationProvider::where('channel', $channel)->where('provider', $provider)->firstOrFail();
    $credentials = array_fill_keys(array_column($record->fields, 'key'), '');
    $required = collect($record->fields)->where('required', true)->pluck('key')->map(fn (string $key) => 'credentials.'.$key)->all();
    $response = $this->actingAs($user)->put(route('communication.update', [$channel, $provider]), [
        'is_active' => true, 'priority' => 1, 'credentials' => $credentials,
    ]);
    if ($required !== []) {
        $response->assertSessionHasErrors($required);
        expect($record->fresh()->is_active)->toBeFalse();
    } else {
        $response->assertSessionHasNoErrors();
        expect($record->fresh()->is_active)->toBeTrue();
    }
    $this->put(route('communication.update', [$channel, $provider]), [
        'is_active' => false, 'priority' => 1, 'credentials' => $credentials,
    ])->assertSessionHasNoErrors();
    expect($record->fresh()->is_active)->toBeFalse();
})->with(fn () => collect(CommunicationRegistry::providers())
    ->mapWithKeys(fn (array $provider) => [$provider['channel'].'/'.$provider['provider'] => [$provider['channel'], $provider['provider']]])
    ->all());

test('unknown provider records are rejected', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['communication.view', 'communication.edit']);
    $this->actingAs($user)->put(route('communication.update', ['unknown-channel', 'unknown-provider']), communicationPayload())->assertNotFound();
});

test('channel settings remain independent and providers sort by priority', function () {
    $smsProviders = CommunicationProvider::where('channel', 'sms')->orderBy('position')->get();
    $smsProviders->each(fn (CommunicationProvider $provider) => $provider->update(['priority' => 1]));
    $expectedOrder = $smsProviders->where('provider', '!=', 'twilio')->pluck('provider')->push('twilio')->all();
    $user = User::factory()->create();
    $user->givePermissionTo(['communication.view', 'communication.edit']);
    $this->actingAs($user)->put(route('communication.update', ['sms', 'twilio']), [
        ...communicationPayload(), 'priority' => 8,
    ])->assertSessionHasNoErrors();
    $this->get(route('communication.index'))->assertInertia(fn (Assert $page) => $page
        ->where('channels', function ($channels) use ($expectedOrder) {
            $sms = collect($channels)->firstWhere('key', 'sms');
            $voice = collect($channels)->firstWhere('key', 'voice');
            $voiceTwilio = collect($voice['providers'])->firstWhere('provider', 'twilio');
            expect(collect($sms['providers'])->pluck('provider')->all())->toBe($expectedOrder)
                ->and($voiceTwilio['is_active'])->toBeFalse()
                ->and($voiceTwilio['saved_secrets'])->toBe([]);

            return true;
        }));
});

test('user management seeding assigns registered communication permissions to admins', function () {
    Permission::factory()->fromRegistry('users.view')->create();
    $role = Role::factory()->create(['name' => 'admin']);
    $role->givePermissionTo('users.view');
    $this->seed(UserManagementSeeder::class);
    $this->seed(UserManagementSeeder::class);
    $permissions = collect(PermissionRegistry::groups()['Communication'])->pluck('name')->all();
    expect($role->fresh()->hasAllPermissions($permissions))->toBeTrue();
    $regularUser = User::where('email', 'user@example.com')->firstOrFail();
    $this->actingAs($regularUser)->get(route('communication.index'))->assertForbidden();
    $this->put(route('communication.update', ['sms', 'twilio']), communicationPayload())->assertForbidden();
});

test('communication seeding preserves admin settings and generates public ids without events', function () {
    $provider = CommunicationProvider::where('channel', 'sms')->where('provider', 'twilio')->firstOrFail();
    $provider->update([...communicationPayload(), 'name' => 'Old provider label', 'fields' => []]);
    $publicId = $provider->public_id;
    CommunicationProvider::where('channel', 'email')->delete();
    CommunicationProvider::withoutEvents(fn () => $this->seed(CommunicationSeeder::class));
    $this->seed(CommunicationSeeder::class);
    expect(CommunicationProvider::count())->toBe(count(CommunicationRegistry::providers()))
        ->and($provider->fresh()->public_id)->toBe($publicId)
        ->and($provider->fresh()->priority)->toBe(2)
        ->and($provider->fresh()->is_active)->toBeTrue()
        ->and($provider->fresh()->credentials['auth_token'])->toBe('secret-token')
        ->and($provider->fresh()->name)->toBe(CommunicationRegistry::channels()['sms']['providers']['twilio']['name'])
        ->and($provider->fresh()->fields)->toBe(CommunicationRegistry::channels()['sms']['providers']['twilio']['fields'])
        ->and(Str::isUlid(CommunicationProvider::where('channel', 'email')->firstOrFail()->public_id))->toBeTrue();
});

test('database definitions drive provider listing and validation', function () {
    $record = CommunicationProvider::create([
        'channel' => 'custom', 'channel_name' => 'Custom channel', 'channel_position' => count(CommunicationRegistry::channels()) + 1,
        'provider' => 'custom-provider', 'name' => 'Custom provider',
        'fields' => [['key' => 'token', 'label' => 'Access token', 'type' => 'password', 'secret' => true, 'required' => true, 'rules' => ['string', 'min:8']]],
    ]);
    $user = User::factory()->create();
    $user->givePermissionTo(['communication.view', 'communication.edit']);
    $this->actingAs($user)->get(route('communication.index'))->assertInertia(fn (Assert $page) => $page
        ->has('channels', count(CommunicationRegistry::channels()) + 1)
        ->where('channels', function ($channels) use ($record) {
            $channel = collect($channels)->firstWhere('key', 'custom');
            $provider = collect($channel['providers'])->firstWhere('provider', 'custom-provider');
            expect($channel['name'])->toBe('Custom channel')
                ->and($provider['name'])->toBe('Custom provider')
                ->and($provider['id'])->toBe($record->public_id)
                ->and($provider['fields'][0])->not->toHaveKey('rules');

            return true;
        }));
    $this->put(route('communication.update', ['custom', 'custom-provider']), [
        'is_active' => true, 'priority' => 1, 'credentials' => ['token' => 'short'],
    ])->assertSessionHasErrors('credentials.token');
    $this->put(route('communication.update', ['custom', 'custom-provider']), [
        'is_active' => true, 'priority' => 1, 'credentials' => ['token' => 'long-token'],
        'name' => 'Injected name', 'fields' => [],
    ])->assertSessionHasNoErrors();
    expect($record->fresh()->is_active)->toBeTrue()
        ->and($record->fresh()->name)->toBe('Custom provider')
        ->and($record->fresh()->credentials['token'])->toBe('long-token');

    $saved = $record->fresh()->getRawOriginal();
    $this->seed(CommunicationSeeder::class);
    expect($record->fresh()->getRawOriginal())->toBe($saved)
        ->and(CommunicationProvider::count())->toBe(count(CommunicationRegistry::providers()) + 1);
});

test('missing records are not recreated by reads or updates', function () {
    CommunicationProvider::where('channel', 'sms')->where('provider', 'twilio')->delete();
    $user = User::factory()->create();
    $user->givePermissionTo(['communication.view', 'communication.edit']);
    $this->actingAs($user)->get(route('communication.index'))->assertInertia(fn (Assert $page) => $page
        ->where('channels', function ($channels) {
            $sms = collect($channels)->firstWhere('key', 'sms');
            expect($sms['providers'])->toHaveCount(count(CommunicationRegistry::channels()['sms']['providers']) - 1)
                ->and(collect($sms['providers'])->contains('provider', 'twilio'))->toBeFalse();

            return true;
        }));
    $this->put(route('communication.update', ['sms', 'twilio']), communicationPayload())->assertNotFound();
    expect(CommunicationProvider::count())->toBe(count(CommunicationRegistry::providers()) - 1);
});
