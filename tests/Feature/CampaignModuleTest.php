<?php

use App\Enums\CampaignStatus;
use App\Enums\CampaignType;
use App\Models\Campaign;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    foreach (['campaigns.view', 'campaigns.create', 'campaigns.edit'] as $permission) {
        Permission::factory()->fromRegistry($permission)->create();
    }

    $this->viewer = User::factory()->create();
    $this->viewer->givePermissionTo('campaigns.view');
    $this->actingAs($this->viewer);
});

test('campaign index returns resource data with type and status metadata', function () {
    $target = Campaign::factory()->create([
        'name' => 'Promo Alpha',
        'short_note' => 'Spring outreach',
        'campaign_type' => CampaignType::OnceOff->value,
        'status' => CampaignStatus::Draft->value,
    ]);
    Campaign::factory()->create([
        'name' => 'Promo Beta',
        'campaign_type' => CampaignType::Ongoing->value,
        'status' => CampaignStatus::Paused->value,
    ]);
    Campaign::factory()->create([
        'name' => 'Billing Alpha',
        'campaign_type' => CampaignType::OnceOff->value,
        'status' => CampaignStatus::Running->value,
    ]);

    $this->get(route('campaigns.index', [
        'search' => 'Promo',
        'filters' => [
            'type' => CampaignType::OnceOff->value,
            'status' => CampaignStatus::Draft->value,
        ],
    ]))->assertInertia(fn (Assert $page) => $page
        ->component('campaigns/Index')
        ->has('campaigns.data', 1)
        ->where('campaigns.data.0.id', $target->public_id)
        ->where('campaigns.data.0.type', CampaignType::OnceOff->toArray())
        ->where('campaigns.data.0.status', CampaignStatus::Draft->toArray())
        ->where('campaigns.data.0.show_url', route('campaigns.once-off.show', $target))
        ->where('campaigns.state.filters.type', (string) CampaignType::OnceOff->value)
        ->where('campaigns.state.filters.status', (string) CampaignStatus::Draft->value)
        ->where('campaigns.options.searchable_columns', ['name', 'short_note'])
        ->where('campaignTypes', CampaignType::options())
        ->where('campaignStatuses', CampaignStatus::options()));
});

test('campaign datatable rejects unsupported query fields', function (array $query, string $field) {
    $this->getJson(route('campaigns.index', $query))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    [['search_column' => 'type'], 'search_column'],
    [['search_column' => 'status'], 'search_column'],
    [['filters' => ['type' => 999]], 'filters.type'],
    [['filters' => ['status' => 999]], 'filters.status'],
    [['filters' => ['unknown' => 'value']], 'filters'],
]);

test('campaign routes require direct or inherited permissions', function () {
    $campaign = Campaign::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('campaigns.index'))->assertForbidden();
    $this->get(route('campaigns.create'))->assertForbidden();
    $this->post(route('campaigns.store'), [])->assertForbidden();
    $this->get(route('campaigns.edit', $campaign))->assertForbidden();

    $role = Role::factory()->create();
    $role->givePermissionTo('campaigns.view');
    $user->assignRole($role);

    $this->get(route('campaigns.index'))->assertSuccessful();

    $this->app['auth']->forgetGuards();
    $this->get(route('campaigns.index'))->assertRedirect(route('login'));
});

test('campaign create defaults status to draft and redirects to type show route', function () {
    $this->viewer->givePermissionTo('campaigns.create');

    $response = $this->post(route('campaigns.store'), [
        'name' => 'Welcome Campaign',
        'short_note' => 'Initial customer outreach',
        'campaign_type' => CampaignType::Ongoing->value,
    ]);

    $campaign = Campaign::query()->where('name', 'Welcome Campaign')->firstOrFail();

    $response->assertRedirect(route('campaigns.ongoing.show', $campaign));

    expect($campaign->public_id)->not->toBeEmpty()
        ->and(Str::isUlid($campaign->public_id))->toBeTrue()
        ->and($campaign->campaign_type)->toBe(CampaignType::Ongoing)
        ->and($campaign->status)->toBe(CampaignStatus::Draft)
        ->and($campaign->short_note)->toBe('Initial customer outreach');
});

test('campaign edit updates only editable fields', function () {
    $this->viewer->givePermissionTo('campaigns.edit');
    $campaign = Campaign::factory()->create([
        'name' => 'Original Campaign',
        'short_note' => 'Original note',
        'campaign_type' => CampaignType::OnceOff->value,
        'status' => CampaignStatus::Running->value,
    ]);

    $response = $this->put(route('campaigns.update', $campaign), [
        'name' => 'Updated Campaign',
        'short_note' => 'Updated note',
        'campaign_type' => CampaignType::BatchProcessing->value,
        'status' => CampaignStatus::Cancelled->value,
    ]);

    $campaign->refresh();

    $response->assertRedirect(route('campaigns.once-off.show', $campaign));

    expect($campaign->name)->toBe('Updated Campaign')
        ->and($campaign->short_note)->toBe('Updated note')
        ->and($campaign->campaign_type)->toBe(CampaignType::OnceOff)
        ->and($campaign->status)->toBe(CampaignStatus::Running);
});

test('campaign show dispatches and type routes render matching pages', function (
    CampaignType $type,
    string $route,
    string $component,
) {
    $campaign = Campaign::factory()->create(['campaign_type' => $type->value]);

    $this->get(route('campaigns.show', $campaign))
        ->assertRedirect(route($route, $campaign));

    $this->get(route($route, $campaign))
        ->assertInertia(fn (Assert $page) => $page
            ->component($component)
            ->where('campaign.id', $campaign->public_id)
            ->where('campaign.type', $type->toArray())
            ->where('campaign.status', CampaignStatus::Draft->toArray()));

    $wrongType = match ($type) {
        CampaignType::OnceOff => CampaignType::Ongoing,
        default => CampaignType::OnceOff,
    };
    $wrongCampaign = Campaign::factory()->create(['campaign_type' => $wrongType->value]);

    $this->get(route($route, $wrongCampaign))->assertNotFound();
})->with([
    'once-off' => [CampaignType::OnceOff, 'campaigns.once-off.show', 'campaigns/once-off/Show'],
    'ongoing' => [CampaignType::Ongoing, 'campaigns.ongoing.show', 'campaigns/ongoing/Show'],
    'batch-processing' => [CampaignType::BatchProcessing, 'campaigns.batch-processing.show', 'campaigns/batch-processing/Show'],
]);
