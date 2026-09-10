<?php

use App\Http\Requests\UserManagement\IndexRoleRequest;
use App\Http\Requests\UserManagement\IndexUserRequest;
use App\Http\Resources\UserManagement\RoleRowResource;
use App\Http\Resources\UserManagement\UserRowResource;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\DataTable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    foreach (['users.view', 'roles.view'] as $permission) {
        Permission::factory()->fromRegistry($permission)->create();
    }
    $this->viewer = User::factory()->create(['first_name' => 'Viewer', 'last_name' => 'Account', 'email' => 'viewer@example.com']);
    $this->viewer->givePermissionTo(['users.view', 'roles.view']);
    $this->actingAs($this->viewer);
});

test('tables paginate on the server and return resource data with metadata', function (string $resource) {
    if ($resource === 'users') {
        User::factory()->count(24)->create();
    } else {
        Role::factory()->count(25)->create();
    }
    $this->get(route("user-management.{$resource}.index", ['page' => 2, 'per_page' => 10]))
        ->assertInertia(fn (Assert $page) => $page
            ->has("{$resource}.data", 10)
            ->where("{$resource}.meta", ['current_page' => 2, 'last_page' => 3, 'per_page' => 10, 'from' => 11, 'to' => 20, 'total' => 25])
            ->where("{$resource}.data", fn ($rows) => collect($rows)->every(fn ($row) => Str::isUlid($row['id'])))
            ->missing("{$resource}.data.0.password")
            ->missing("{$resource}.data.0.public_id"));
    $this->get(route("user-management.{$resource}.index", ['per_page' => 25]))
        ->assertInertia(fn (Assert $page) => $page->has("{$resource}.data", 25)->where("{$resource}.meta.last_page", 1));
})->with(['users', 'roles']);

test('user searches support full names and email but exclude roles', function () {
    $target = User::factory()->create(['first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@analytical.test']);
    $target->assignRole(Role::factory()->create(['display_name' => 'Engine Operator']));
    User::factory()->create(['first_name' => 'Other', 'last_name' => 'Person', 'email' => 'other@example.test']);
    foreach ([['Ada Lovelace', 'full_name'], ['analytical', 'email'], ['Lovelace', '']] as [$search, $column]) {
        $this->get(route('user-management.users.index', ['search' => $search, 'search_column' => $column]))
            ->assertInertia(fn (Assert $page) => $page->has('users.data', 1)->where('users.data.0.id', $target->public_id)->where('users.meta.total', 1));
    }
    $this->get(route('user-management.users.index', ['search' => 'analytical', 'search_column' => 'full_name']))
        ->assertInertia(fn (Assert $page) => $page->has('users.data', 0)->where('users.meta.from', null)->where('users.meta.to', null));
    $this->get(route('user-management.users.index', ['search' => 'Engine Operator']))
        ->assertInertia(fn (Assert $page) => $page->has('users.data', 0)->where('users.options.searchable_columns', ['full_name', 'email']));
    $this->getJson(route('user-management.users.index', ['search_column' => 'roles']))
        ->assertUnprocessable()->assertJsonValidationErrors('search_column');
});

test('users can be filtered by role combined with search and cleared', function () {
    $role = Role::factory()->create(['name' => 'editor', 'display_name' => 'Editors']);
    $target = User::factory()->create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);
    $target->assignRole($role);
    $other = User::factory()->create(['first_name' => 'Grace', 'last_name' => 'Hopper']);
    $other->assignRole($role);

    $this->get(route('user-management.users.index', ['filters' => ['role' => 'editor']]))
        ->assertInertia(fn (Assert $page) => $page->has('users.data', 2)
            ->where('users.state.filters.role', 'editor')
            ->where('roleOptions', [['name' => 'editor', 'display_name' => 'Editors']]));
    $this->get(route('user-management.users.index', ['filters' => ['role' => 'editor'], 'search' => 'Ada']))
        ->assertInertia(fn (Assert $page) => $page->has('users.data', 1)->where('users.data.0.id', $target->public_id));
    $this->get(route('user-management.users.index', ['filters' => ['role' => '']]))
        ->assertInertia(fn (Assert $page) => $page->has('users.data', 3)->where('users.state.filters.role', null));
    $this->getJson(route('user-management.users.index', ['filters' => ['role' => 'missing-role']]))
        ->assertUnprocessable()->assertJsonValidationErrors('filters.role');
});

test('role search supports names notes and exact assigned user counts', function () {
    $target = Role::factory()->create(['display_name' => 'Editors', 'name' => 'content-editor', 'short_note' => 'Publishing access']);
    $this->viewer->assignRole($target);
    Role::factory()->create(['display_name' => 'Observers', 'short_note' => 'Read only']);
    foreach ([['Editors', 'display_name'], ['content-editor', ''], ['Publishing', 'short_note'], ['1', 'users_count']] as [$search, $column]) {
        $this->get(route('user-management.roles.index', ['search' => $search, 'search_column' => $column]))
            ->assertInertia(fn (Assert $page) => $page->has('roles.data', 1)->where('roles.data.0.id', $target->public_id)->where('roles.data.0.users_count', 1));
    }
    $this->get(route('user-management.roles.index', ['search' => 'Editors', 'search_column' => 'short_note']))
        ->assertInertia(fn (Assert $page) => $page->has('roles.data', 0));
});

test('tables sort both directions including relationship counts', function (string $resource, string $sort) {
    if ($resource === 'users') {
        $low = User::factory()->create(['first_name' => 'Aaa', 'email' => 'aaa@example.test', 'created_at' => '2020-01-01']);
        $high = User::factory()->create(['first_name' => 'Zzz', 'email' => 'zzz@example.test', 'created_at' => '2030-01-01']);
    } else {
        $low = Role::factory()->create(['display_name' => 'Aaa', 'short_note' => 'Aaa', 'created_at' => '2020-01-01']);
        $high = Role::factory()->create(['display_name' => 'Zzz', 'short_note' => 'Zzz', 'created_at' => '2030-01-01']);
        $this->viewer->assignRole($high);
    }
    foreach (['asc' => $low, 'desc' => $high] as $direction => $first) {
        $this->get(route("user-management.{$resource}.index", ['sort' => $sort, 'direction' => $direction]))
            ->assertInertia(fn (Assert $page) => $page->where("{$resource}.data.0.id", $first->public_id)->where("{$resource}.state.sort", $sort)->where("{$resource}.state.direction", $direction));
    }
})->with([
    ['users', 'full_name'], ['users', 'email'], ['users', 'created_at'],
    ['roles', 'display_name'], ['roles', 'short_note'], ['roles', 'users_count'], ['roles', 'created_at'],
]);

test('invalid query fields and page sizes are rejected', function (array $query, string $field) {
    foreach (['users', 'roles'] as $resource) {
        $this->getJson(route("user-management.{$resource}.index", $query))->assertUnprocessable()->assertJsonValidationErrors($field);
    }
})->with([
    [['sort' => 'password'], 'sort'], [['sort' => 'id desc; DROP TABLE users'], 'sort'],
    [['search_column' => 'id'], 'search_column'], [['search_column' => 'created_at'], 'search_column'],
    [['per_page' => 100000], 'per_page'], [['per_page' => 0], 'per_page'],
    [['page' => -1], 'page'], [['page' => 'bad'], 'page'],
    [['direction' => 'invalid'], 'direction'], [['search' => ['array']], 'search'],
    [['search' => str_repeat('a', 256)], 'search'],
]);

test('out of range pages recover and clearing search returns all records', function (string $resource) {
    if ($resource === 'users') {
        User::factory()->count(11)->create();
    } else {
        Role::factory()->count(12)->create();
    }
    $this->get(route("user-management.{$resource}.index", ['page' => 999]))
        ->assertInertia(fn (Assert $page) => $page->has("{$resource}.data", 2)->where("{$resource}.meta.current_page", 2));
    $this->get(route("user-management.{$resource}.index", ['search' => 'unmatched-value', 'page' => 999]))
        ->assertInertia(fn (Assert $page) => $page->has("{$resource}.data", 0)->where("{$resource}.meta.current_page", 1)->where("{$resource}.meta.total", 0));
    $this->get(route("user-management.{$resource}.index", ['search' => '', 'search_column' => '', 'page' => 1]))
        ->assertInertia(fn (Assert $page) => $page->has("{$resource}.data", 10)->where("{$resource}.meta.total", 12));
})->with(['users', 'roles']);

test('table requests require direct or inherited view permission', function (string $resource) {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route("user-management.{$resource}.index", ['search' => 'test']))->assertForbidden();
    $role = Role::factory()->create();
    $role->givePermissionTo("{$resource}.view");
    $user->assignRole($role);
    $this->get(route("user-management.{$resource}.index"))->assertSuccessful();
    $this->app['auth']->forgetGuards();
    $this->get(route("user-management.{$resource}.index"))->assertRedirect(route('login'));
})->with(['users', 'roles']);

test('partial reloads return table state and refreshed authorization', function () {
    $this->get(route('user-management.users.index', ['search' => 'Viewer']))
        ->assertInertia(fn (Assert $page) => $page
            ->reloadOnly(['users', 'auth'], fn (Assert $reload) => $reload
                ->has('users.data', 1)
                ->where('users.state.search', 'Viewer')
                ->has('auth.permissions', 2)
                ->missing('name')));
});

test('reusable table respects custom defaults page sizes and scoped queries', function () {
    Role::factory()->count(8)->create(['short_note' => 'included']);
    Role::factory()->create(['short_note' => 'excluded']);
    $request = new class extends IndexRoleRequest
    {
        public function perPageOptions(): array
        {
            return [3, 6];
        }

        public function defaultPerPage(): int
        {
            return 3;
        }
    };
    $request->setContainer($this->app);
    $request->setUserResolver(fn () => $this->viewer);
    $request->validateResolved();
    $data = DataTable::make(Role::query()->where('short_note', 'included')->withCount('users'), $request, RoleRowResource::class);
    expect($data['data'])->toHaveCount(3);
    expect($data['meta']['total'])->toBe(8);
    expect($data['options']['per_page_options'])->toBe([3, 6]);
    expect($data['options']['defaults'])->toBe([
        'page' => 1, 'per_page' => 3, 'search' => '', 'search_column' => '', 'sort' => 'display_name', 'direction' => 'asc',
    ]);
});

class FilteredRolesTableRequest extends IndexRoleRequest
{
    public function filterRules(): array
    {
        return ['assigned' => ['nullable', 'boolean'], 'names' => ['nullable', 'array'], 'names.*' => ['string', 'max:255']];
    }

    public function filterDefaults(): array
    {
        return ['assigned' => null, 'names' => []];
    }

    public function applyFilters(Builder $query, array $filters): void
    {
        if ($filters['assigned'] !== null) {
            $query->has('users', (bool) $filters['assigned'] ? '>' : '=', 0);
        }
        if ($filters['names'] !== []) {
            $query->whereIn('name', $filters['names']);
        }
    }
}

function registerMultiTablePage(): void
{
    Route::middleware('web')->get('/test-tables', fn (Request $request) => Inertia::render('user-management/users/Index', [
        'summary' => fn () => ['users' => User::count(), 'roles' => Role::count()],
        'users' => fn () => DataTable::make(User::query()->with('roles'), IndexUserRequest::forTable($request, 'users'), UserRowResource::class),
        'roles' => fn () => DataTable::make(Role::query()->withCount('users'), FilteredRolesTableRequest::forTable($request, 'roles'), RoleRowResource::class),
    ]));
}

test('two tables have independent validated search sort filters and pagination', function () {
    registerMultiTablePage();
    User::factory()->count(12)->create();
    $role = Role::factory()->create(['display_name' => 'Editors', 'name' => 'editors']);
    $this->viewer->assignRole($role);
    Role::factory()->create(['display_name' => 'Observers']);

    $this->get('/test-tables?'.http_build_query([
        'tab' => 'overview',
        'users' => ['page' => 2, 'sort' => 'email', 'direction' => 'asc'],
        'roles' => ['search' => 'Editors', 'sort' => 'users_count', 'direction' => 'desc', 'filters' => ['assigned' => '1', 'names' => ['editors']]],
    ]))->assertInertia(fn (Assert $page) => $page
        ->where('users.meta.current_page', 2)->has('users.data', 3)
        ->where('users.state.sort', 'email')->where('users.options.query_namespace', 'users')
        ->has('roles.data', 1)->where('roles.data.0.id', $role->public_id)
        ->where('roles.state.filters.assigned', '1')->where('roles.options.query_namespace', 'roles')
        ->where('summary', ['users' => 13, 'roles' => 2])
        ->reloadOnly(['roles', 'auth'], fn (Assert $reload) => $reload
            ->has('roles.data', 1)->missing('users')->missing('summary')));
});

test('custom filters combine with search and clear back to defaults', function () {
    registerMultiTablePage();
    $assigned = Role::factory()->create(['display_name' => 'Shared name']);
    $this->viewer->assignRole($assigned);
    $unassigned = Role::factory()->create(['display_name' => 'Shared name']);

    $this->get('/test-tables?'.http_build_query(['roles' => ['search' => 'Shared', 'filters' => ['assigned' => '0']]]))
        ->assertInertia(fn (Assert $page) => $page->has('roles.data', 1)->where('roles.data.0.id', $unassigned->public_id));
    $this->get('/test-tables?'.http_build_query(['roles' => ['search' => 'Shared']]))
        ->assertInertia(fn (Assert $page) => $page->has('roles.data', 2)->where('roles.state.filters', ['assigned' => null, 'names' => []]));
});

test('invalid custom filters and table input report namespaced errors', function (array $input, string $error) {
    registerMultiTablePage();
    $this->getJson('/test-tables?'.http_build_query($input))->assertUnprocessable()->assertJsonValidationErrors($error);
})->with([
    [['roles' => ['filters' => ['assigned' => 'invalid']]], 'roles.filters.assigned'],
    [['roles' => ['filters' => ['unknown' => 'secret']]], 'roles.filters'],
    [['roles' => ['filters' => ['names' => ['editors', ['bad']]]]], 'roles.filters.names.1'],
    [['roles' => ['sort' => 'password']], 'roles.sort'],
    [['users' => 'invalid'], 'users'],
]);

test('named table requests retain authorization and do not mutate the parent request', function () {
    $parent = Request::create('/test-tables', 'GET', ['tab' => 'overview', 'users' => ['page' => 2], 'roles' => ['page' => 3]]);
    $parent->setUserResolver(fn () => $this->viewer);
    $users = IndexUserRequest::forTable($parent, 'users');
    $roles = IndexRoleRequest::forTable($parent, 'roles');
    expect($users->validated('users.page'))->toBe(2);
    expect($roles->validated('roles.page'))->toBe(3);
    expect($parent->query('tab'))->toBe('overview');
    expect($parent->query('roles'))->toBe(['page' => 3]);

    $this->viewer->revokePermissionTo('roles.view');
    expect(fn () => IndexRoleRequest::forTable($parent, 'roles'))->toThrow(AuthorizationException::class);
});
