<?php

use App\Enums\CampaignStatus;
use App\Enums\CampaignType;
use App\Enums\ContactImportStatus;
use App\Enums\ContactUploadMode;
use App\Enums\ContactUploadRowStatus;
use App\Enums\ContactUploadSource;
use App\Models\Campaign;
use App\Models\Campaigns\OnceOffCampaign;
use App\Models\OnceOffCampaignContact;
use App\Models\OnceOffCampaignContactImport;
use App\Models\OnceOffCampaignContactUploadRow;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

beforeEach(function () {
    Storage::fake('local');
    foreach (['campaigns.view', 'campaigns.edit'] as $permission) {
        Permission::factory()->fromRegistry($permission)->create();
    }
    $this->editor = User::factory()->create();
    $this->editor->givePermissionTo(['campaigns.view', 'campaigns.edit']);
    $this->actingAs($this->editor);
    $this->campaign = OnceOffCampaign::factory()->create();
});

function contactCsv(string $rows = "Alice,,+14155550101,\nBob,,+14155550102,\n"): UploadedFile
{
    return UploadedFile::fake()->createWithContent('contacts.csv', "first_name,last_name,number,email\n".$rows);
}

function stageContactFile(OnceOffCampaign $campaign, ContactUploadMode $mode = ContactUploadMode::Append, ?UploadedFile $file = null): OnceOffCampaignContactImport
{
    $response = test()->post(route('campaigns.once-off.contact-imports.store', $campaign), [
        'file' => $file ?? contactCsv(), 'mode' => $mode->value,
    ])->assertSessionHasNoErrors();
    $upload = $campaign->contactImports()->latest('id')->firstOrFail();
    $response->assertRedirect(route('campaigns.once-off.contact-imports.show', [$campaign, $upload]));

    return $upload;
}

test('once-off tab routes load only their section data', function (string $route, string $component, array $present, array $absent) {
    $this->get(route($route, $this->campaign))->assertInertia(function (Assert $page) use ($component, $present, $absent) {
        $page->component('campaigns/once-off/'.$component)->where('campaign.id', $this->campaign->public_id);
        foreach ($present as $prop) {
            $page->has($prop);
        }
        foreach ($absent as $prop) {
            $page->missing($prop);
        }
    });
    $otherType = Campaign::factory()->create(['campaign_type' => CampaignType::Ongoing]);
    $this->get(route($route, $otherType))->assertNotFound();
})->with([
    ['campaigns.once-off.show', 'Show', ['contactSummary'], ['contacts', 'contactImports', 'uploadModes']],
    ['campaigns.once-off.contacts.index', 'Contacts', ['contacts'], ['contactSummary', 'contactImports']],
    ['campaigns.once-off.contact-imports.index', 'UploadContacts', ['contactImports', 'importStatuses', 'uploadModes'], ['contacts', 'contactSummary']],
    ['campaigns.once-off.schedule.show', 'Schedule', ['schedules', 'channels'], ['contacts', 'contactImports', 'contactSummary']],
]);

test('contact upload endpoints require both view and edit permissions', function (array $permissions) {
    $upload = stageContactFile($this->campaign);
    $user = User::factory()->create();
    foreach ($permissions as $permission) {
        $user->givePermissionTo($permission);
    }
    $this->actingAs($user);
    foreach (['index', 'show', 'download'] as $action) {
        $this->get(route('campaigns.once-off.contact-imports.'.$action, [$this->campaign, 'contactImport' => $upload]))
            ->assertForbidden();
    }
    foreach (['store', 'preview', 'sync'] as $action) {
        $this->post(route('campaigns.once-off.contact-imports.'.$action, [$this->campaign, 'contactImport' => $upload]), [])
            ->assertForbidden();
    }
    $this->post(route('campaigns.once-off.contacts.store', $this->campaign), [])->assertForbidden();
    expect(OnceOffCampaignContact::count())->toBe(0)
        ->and(OnceOffCampaignContactImport::count())->toBe(1);
})->with(['none' => [[]], 'view only' => [['campaigns.view']], 'edit only' => [['campaigns.edit']]]);

test('view permission grants read tabs while inherited permissions grant upload access', function () {
    $user = User::factory()->create();
    foreach (['show', 'contacts.index', 'schedule.show'] as $action) {
        $this->actingAs($user)->get(route('campaigns.once-off.'.$action, $this->campaign))->assertForbidden();
    }
    $role = Role::factory()->create();
    $role->givePermissionTo('campaigns.view');
    $user->assignRole($role);
    foreach (['show', 'contacts.index', 'schedule.show'] as $action) {
        $this->get(route('campaigns.once-off.'.$action, $this->campaign))->assertSuccessful();
    }
    $role->givePermissionTo('campaigns.edit');
    $this->get(route('campaigns.once-off.contact-imports.index', $this->campaign))->assertSuccessful();
    stageContactFile($this->campaign);
});

test('manual contacts are staged with optional fields empty until explicitly synced', function () {
    $this->post(route('campaigns.once-off.contacts.store', $this->campaign), [
        'first_name' => 'Alice', 'number' => '+1 (415) 555-0101', 'mode' => ContactUploadMode::Append->value,
    ])->assertSessionHasNoErrors()->assertRedirect();
    $upload = $this->campaign->contactImports()->sole();
    $row = $upload->uploadedRows()->sole();
    expect($upload->source)->toBe(ContactUploadSource::Manual)
        ->and($upload->status)->toBe(ContactImportStatus::Pending)
        ->and($upload->file_path)->toBeNull()
        ->and($upload->uploaded_by)->toBe($this->editor->id)
        ->and($row->normalized_number)->toBe('14155550101')
        ->and($row->last_name)->toBeNull()
        ->and($row->email)->toBeNull()
        ->and(OnceOffCampaignContact::count())->toBe(0);
    $this->post(route('campaigns.once-off.contact-imports.sync', [$this->campaign, $upload]))
        ->assertSessionHasNoErrors()->assertRedirect();
    expect($this->campaign->contacts()->sole()->first_name)->toBe('Alice')
        ->and($row->fresh()->status)->toBe(ContactUploadRowStatus::Added)
        ->and($upload->fresh()->status)->toBe(ContactImportStatus::Synced);
});

test('manual validation rejects invalid contacts and replacement mode without staging', function (array $values, string $field) {
    $this->postJson(route('campaigns.once-off.contacts.store', $this->campaign), [
        'first_name' => 'Alice', 'number' => '+14155550101', 'mode' => ContactUploadMode::Append->value, ...$values,
    ])->assertUnprocessable()->assertJsonValidationErrors($field);
    expect(OnceOffCampaignContactImport::count())->toBe(0)->and(OnceOffCampaignContact::count())->toBe(0);
})->with([
    [['first_name' => ''], 'first_name'],
    [['number' => '4155550101'], 'number'],
    [['number' => '+123'], 'number'],
    [['email' => 'invalid'], 'email'],
    [['mode' => ContactUploadMode::Replace->value], 'mode'],
]);

test('file validation reports row and cell errors and invalid store saves nothing', function () {
    $file = contactCsv("Alice,,+14155550101,\n,,bad,invalid\n");
    $this->postJson(route('campaigns.once-off.contact-imports.preview', $this->campaign), [
        'file' => $file, 'mode' => ContactUploadMode::Append->value,
    ])->assertSuccessful()->assertJsonPath('rows.1.row_number', 3)
        ->assertJsonStructure(['rows' => [1 => ['errors' => ['0', '2', '3']]]])
        ->assertJsonMissingPath('contacts');
    $this->postJson(route('campaigns.once-off.contact-imports.store', $this->campaign), [
        'file' => $file, 'mode' => ContactUploadMode::Append->value,
    ])->assertUnprocessable()->assertJsonValidationErrors('file');
    expect(OnceOffCampaignContactImport::count())->toBe(0)
        ->and(OnceOffCampaignContactUploadRow::count())->toBe(0)
        ->and(OnceOffCampaignContact::count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([]);
});

test('invalid file structures and duplicate numbers are rejected as a whole', function (string $contents) {
    $this->postJson(route('campaigns.once-off.contact-imports.store', $this->campaign), [
        'file' => UploadedFile::fake()->createWithContent('contacts.csv', $contents), 'mode' => ContactUploadMode::Append->value,
    ])->assertUnprocessable()->assertJsonValidationErrors('file');
    expect(OnceOffCampaignContactImport::count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([]);
})->with([
    'missing header' => ["first_name,email\nAlice,alice@example.com\n"],
    'duplicate header' => ["first_name,number,number\nAlice,+14155550101,+14155550102\n"],
    'empty file' => ["first_name,number\n"],
    'duplicate normalized number' => ["first_name,number\nAlice,+1 (415) 555-0101\nBob,+14155550101\n"],
    'formula' => ["first_name,number\n=1+1,+14155550101\n"],
    'extra cells' => ["first_name,number\nAlice,+14155550101,extra\n"],
]);

test('successful validation creates no records and store revalidates changed input', function () {
    $this->postJson(route('campaigns.once-off.contact-imports.preview', $this->campaign), [
        'file' => contactCsv(), 'mode' => ContactUploadMode::Append->value,
    ])->assertSuccessful()->assertJsonPath('error_count', 0);
    expect(OnceOffCampaignContactImport::count())->toBe(0)->and(Storage::disk('local')->allFiles())->toBe([]);
    $this->postJson(route('campaigns.once-off.contact-imports.store', $this->campaign), [
        'file' => contactCsv("Alice,,invalid,\n"), 'mode' => ContactUploadMode::Append->value,
    ])->assertUnprocessable();
    expect(OnceOffCampaignContactImport::count())->toBe(0);
});

test('Excel uploads retain a private original and expose public row metadata', function (string $format) {
    $spreadsheet = new Spreadsheet;
    $spreadsheet->getActiveSheet()->fromArray([['first_name', 'number'], ['Alice', '+14155550101']]);
    $spreadsheet->getActiveSheet()->setCellValueExplicit('B2', '+14155550101', DataType::TYPE_STRING);
    $temporary = tmpfile();
    try {
        $path = stream_get_meta_data($temporary)['uri'];
        IOFactory::createWriter($spreadsheet, $format)->save($path);
        $file = UploadedFile::fake()->createWithContent('contacts.'.strtolower($format), file_get_contents($path));
        $upload = stageContactFile($this->campaign, file: $file);
    } finally {
        fclose($temporary);
        $spreadsheet->disconnectWorksheets();
    }
    $row = $upload->uploadedRows()->sole();
    Storage::disk('local')->assertExists($upload->file_path);
    expect($upload->source)->toBe(ContactUploadSource::File)->and(OnceOffCampaignContact::count())->toBe(0);
    $this->get(route('campaigns.once-off.contact-imports.show', [$this->campaign, $upload]))
        ->assertInertia(fn (Assert $page) => $page->component('campaigns/once-off/UploadShow')
            ->where('campaign.id', $this->campaign->public_id)
            ->where('upload.id', $upload->public_id)->where('upload.source', ContactUploadSource::File->toArray())
            ->where('upload.mode', ContactUploadMode::Append->toArray())->missing('upload.file_path')
            ->where('rows.data.0.id', $row->public_id)->where('rows.data.0.status', ContactUploadRowStatus::Pending->toArray())
            ->where('rows.data.0.planned_action', 'add')->missing('rows.data.0.contact_import_id')
            ->where('syncPlan.add', 1));
    $this->get(route('campaigns.once-off.contact-imports.download', [$this->campaign, $upload]))
        ->assertDownload($upload->file_name);
})->with(['Xlsx', 'Xls']);

test('upload details download and sync reject another campaign upload', function () {
    $upload = stageContactFile($this->campaign);
    $other = OnceOffCampaign::factory()->create();
    foreach (['show', 'download'] as $action) {
        $this->get(route('campaigns.once-off.contact-imports.'.$action, [$other, $upload]))->assertNotFound();
    }
    $this->post(route('campaigns.once-off.contact-imports.sync', [$other, $upload]))->assertNotFound();
    expect(OnceOffCampaignContact::count())->toBe(0);
});

test('selected sync tracks partial completion and repeated sync does not add duplicates', function () {
    $upload = stageContactFile($this->campaign);
    $rows = $upload->uploadedRows()->orderBy('row_number')->get();
    $url = route('campaigns.once-off.contact-imports.sync', [$this->campaign, $upload]);
    $this->post($url, ['row_ids' => [$rows[0]->public_id]])->assertSessionHasNoErrors();
    expect($upload->fresh()->status)->toBe(ContactImportStatus::PartiallySynced)
        ->and($rows[1]->fresh()->status)->toBe(ContactUploadRowStatus::Pending)
        ->and($this->campaign->contacts()->count())->toBe(1);
    $this->post($url)->assertSessionHasNoErrors();
    $this->post($url)->assertSessionHasNoErrors();
    expect($upload->fresh()->status)->toBe(ContactImportStatus::Synced)
        ->and($upload->fresh()->synced_at)->not->toBeNull()
        ->and($this->campaign->contacts()->count())->toBe(2);
});

test('sync rejects row IDs from another upload without processing valid selections', function () {
    $upload = stageContactFile($this->campaign);
    $other = stageContactFile($this->campaign);
    $this->postJson(route('campaigns.once-off.contact-imports.sync', [$this->campaign, $upload]), [
        'row_ids' => [$upload->uploadedRows()->first()->public_id, $other->uploadedRows()->first()->public_id],
    ])->assertUnprocessable()->assertJsonValidationErrors('sync');
    expect(OnceOffCampaignContact::count())->toBe(0)->and($upload->fresh()->status)->toBe(ContactImportStatus::Pending);
});

test('append and update match normalized numbers only within the campaign', function (ContactUploadMode $mode, ContactUploadRowStatus $outcome, string $name) {
    $contact = $this->campaign->contacts()->create([
        'first_name' => 'Original', 'last_name' => 'Kept', 'number' => '+1 (415) 555-0101', 'email' => 'kept@example.com',
    ]);
    $other = OnceOffCampaign::factory()->create();
    $outside = $other->contacts()->create(['first_name' => 'Outside', 'number' => '+14155550102']);
    $upload = stageContactFile($this->campaign, $mode);
    $this->post(route('campaigns.once-off.contact-imports.sync', [$this->campaign, $upload]))->assertSessionHasNoErrors();
    $row = $upload->uploadedRows()->where('row_number', 2)->firstOrFail();
    expect($contact->fresh()->first_name)->toBe($name)
        ->and($contact->fresh()->last_name)->toBe('Kept')->and($contact->fresh()->email)->toBe('kept@example.com')
        ->and($row->status)->toBe($outcome)->and($row->before_values['first_name'])->toBe('Original')
        ->and($outside->fresh()->first_name)->toBe('Outside')
        ->and($this->campaign->contacts()->count())->toBe(2);
})->with([
    [ContactUploadMode::Append, ContactUploadRowStatus::Skipped, 'Original'],
    [ContactUploadMode::Update, ContactUploadRowStatus::Updated, 'Alice'],
]);

test('sync revalidates staged rows and can retry a corrected failure', function () {
    $upload = stageContactFile($this->campaign);
    $row = $upload->uploadedRows()->first();
    $row->update(['email' => 'invalid']);
    $url = route('campaigns.once-off.contact-imports.sync', [$this->campaign, $upload]);
    $this->post($url)->assertSessionHasNoErrors();
    expect($row->fresh()->status)->toBe(ContactUploadRowStatus::Failed)
        ->and($row->fresh()->error)->not->toBeNull()
        ->and($upload->fresh()->status)->toBe(ContactImportStatus::PartiallySynced)
        ->and($this->campaign->contacts()->count())->toBe(1);
    $row->update(['email' => null]);
    $this->post($url, ['row_ids' => [$row->public_id]])->assertSessionHasNoErrors();
    expect($row->fresh()->status)->toBe(ContactUploadRowStatus::Added)
        ->and($row->fresh()->error)->toBeNull()
        ->and($this->campaign->contacts()->count())->toBe(2);
});

test('replacement removes absent contacts with retained history after confirmation', function () {
    $removed = $this->campaign->contacts()->create(['first_name' => 'Remove', 'number' => '+14155550999']);
    $upload = stageContactFile($this->campaign, ContactUploadMode::Replace);
    $plan = $this->get(route('campaigns.once-off.contact-imports.show', [$this->campaign, $upload]))->inertiaProps('syncPlan');
    expect($plan['remove'])->toBe(1)->and($plan['add'])->toBe(2);
    $this->post(route('campaigns.once-off.contact-imports.sync', [$this->campaign, $upload]), [
        'fingerprint' => $plan['fingerprint'],
    ])->assertSessionHasNoErrors();
    $this->assertSoftDeleted($removed);
    expect($removed->fresh()->removed_by_import_id)->toBe($upload->id)
        ->and($upload->fresh()->removed_count)->toBe(1)
        ->and($upload->fresh()->status)->toBe(ContactImportStatus::Synced)
        ->and($this->campaign->contacts()->count())->toBe(2);
});

test('replacement rejects missing stale partial and non-draft confirmations without writes', function (string $scenario) {
    $original = $this->campaign->contacts()->create(['first_name' => 'Original', 'number' => '+14155550999']);
    $upload = stageContactFile($this->campaign, ContactUploadMode::Replace);
    $plan = $this->get(route('campaigns.once-off.contact-imports.show', [$this->campaign, $upload]))->inertiaProps('syncPlan');
    $payload = ['fingerprint' => $plan['fingerprint']];
    if ($scenario === 'stale') {
        $original->update(['first_name' => 'Changed']);
    } elseif ($scenario === 'partial') {
        $payload['row_ids'] = [$upload->uploadedRows()->first()->public_id];
    } elseif ($scenario === 'non-draft') {
        $this->campaign->update(['status' => CampaignStatus::Running]);
    } else {
        $payload = [];
    }
    $this->postJson(route('campaigns.once-off.contact-imports.sync', [$this->campaign, $upload]), $payload)
        ->assertUnprocessable()->assertJsonValidationErrors('sync');
    expect($this->campaign->contacts()->count())->toBe(1)
        ->and($original->fresh()->deleted_at)->toBeNull()
        ->and($upload->fresh()->status)->toBe(ContactImportStatus::Pending)
        ->and($upload->uploadedRows()->whereNotNull('synced_at')->count())->toBe(0);
})->with(['missing', 'stale', 'partial', 'non-draft']);

test('a failed replacement rolls back contacts already processed earlier in the file', function () {
    $original = $this->campaign->contacts()->create(['first_name' => 'Original', 'number' => '+14155550101']);
    $upload = stageContactFile($this->campaign, ContactUploadMode::Replace);
    $upload->uploadedRows()->where('row_number', 3)->update(['email' => 'invalid']);
    $plan = $this->get(route('campaigns.once-off.contact-imports.show', [$this->campaign, $upload]))->inertiaProps('syncPlan');
    $this->postJson(route('campaigns.once-off.contact-imports.sync', [$this->campaign, $upload]), [
        'fingerprint' => $plan['fingerprint'],
    ])->assertUnprocessable()->assertJsonValidationErrors('sync');
    expect($original->fresh()->first_name)->toBe('Original')
        ->and($this->campaign->contacts()->count())->toBe(1)
        ->and($upload->uploadedRows()->whereNotNull('synced_at')->count())->toBe(0);
});

test('non-draft replacement staging cleans up its file', function () {
    $this->campaign->update(['status' => CampaignStatus::Running]);
    $this->postJson(route('campaigns.once-off.contact-imports.store', $this->campaign), [
        'file' => contactCsv(), 'mode' => ContactUploadMode::Replace->value,
    ])->assertUnprocessable()->assertJsonValidationErrors('mode');
    expect(OnceOffCampaignContactImport::count())->toBe(0)->and(Storage::disk('local')->allFiles())->toBe([]);
});

test('contact datatable searches and paginates only active contacts in its campaign', function () {
    $target = $this->campaign->contacts()->create(['first_name' => 'Alice', 'number' => '+14155550101']);
    $this->campaign->contacts()->create(['first_name' => 'Bob', 'number' => '+14155550102']);
    $deleted = $this->campaign->contacts()->create(['first_name' => 'Alice removed', 'number' => '+14155550103']);
    $deleted->delete();
    OnceOffCampaign::factory()->create()->contacts()->create(['first_name' => 'Alice elsewhere', 'number' => '+14155550104']);
    $this->get(route('campaigns.once-off.contacts.index', [
        $this->campaign, 'contacts' => ['search' => 'Alice', 'search_column' => 'first_name', 'per_page' => 10],
    ]))->assertInertia(fn (Assert $page) => $page->has('contacts.data', 1)
        ->where('contacts.data.0.id', $target->public_id)->missing('contacts.data.0.campaign_id')
        ->where('contacts.state.search', 'Alice')->where('contacts.state.per_page', 10));
});

test('upload and row datatables filter by outcome with campaign scope', function () {
    $upload = stageContactFile($this->campaign);
    $first = $upload->uploadedRows()->orderBy('row_number')->first();
    $this->post(route('campaigns.once-off.contact-imports.sync', [$this->campaign, $upload]), [
        'row_ids' => [$first->public_id],
    ])->assertSessionHasNoErrors();
    stageContactFile($this->campaign);
    $this->get(route('campaigns.once-off.contact-imports.index', [
        $this->campaign, 'imports' => ['search' => 'contacts.csv', 'filters' => ['status' => ContactImportStatus::PartiallySynced->value]],
    ]))->assertInertia(fn (Assert $page) => $page->has('contactImports.data', 1)
        ->where('contactImports.data.0.id', $upload->public_id)
        ->where('contactImports.data.0.processed_count', 1));
    $this->get(route('campaigns.once-off.contact-imports.show', [
        $this->campaign, $upload, 'rows' => ['filters' => ['status' => ContactUploadRowStatus::Added->value]],
    ]))->assertInertia(fn (Assert $page) => $page->has('rows.data', 1)
        ->where('rows.data.0.id', $first->public_id)->where('rows.data.0.planned_action', null));
});

test('contact datatables reject unsupported search sort and filter fields', function (string $action, array $query, string $error) {
    $upload = stageContactFile($this->campaign);
    $this->getJson(route('campaigns.once-off.'.$action, [
        $this->campaign, 'contactImport' => $upload, ...$query,
    ]))->assertUnprocessable()->assertJsonValidationErrors($error);
})->with([
    ['contacts.index', ['contacts' => ['search_column' => 'campaign_id']], 'contacts.search_column'],
    ['contacts.index', ['contacts' => ['sort' => 'public_id']], 'contacts.sort'],
    ['contact-imports.index', ['imports' => ['filters' => ['status' => 999]]], 'imports.filters.status'],
    ['contact-imports.index', ['imports' => ['filters' => ['unknown' => 'value']]], 'imports.filters'],
    ['contact-imports.show', ['rows' => ['filters' => ['status' => 999]]], 'rows.filters.status'],
    ['contact-imports.show', ['rows' => ['search_column' => 'contact_id']], 'rows.search_column'],
]);

test('file size extension and row limits are enforced without saving uploads', function (string $kind) {
    $file = match ($kind) {
        'extension' => UploadedFile::fake()->createWithContent('contacts.txt', "first_name,number\nAlice,+14155550101"),
        'size' => contactCsv()->size(2049),
        'rows' => contactCsv(implode("\n", array_map(fn (int $row): string => 'Alice,,+1415555'.str_pad((string) $row, 4, '0', STR_PAD_LEFT).',', range(1, 5001)))),
    };
    $this->postJson(route('campaigns.once-off.contact-imports.store', $this->campaign), [
        'file' => $file, 'mode' => ContactUploadMode::Append->value,
    ])->assertUnprocessable()->assertJsonValidationErrors('file');
    expect(OnceOffCampaignContactImport::count())->toBe(0)->and(Storage::disk('local')->allFiles())->toBe([]);
})->with(['extension', 'size', 'rows']);
