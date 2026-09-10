<?php

namespace App\Http\Controllers\UserManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserManagement\IndexUserRequest;
use App\Http\Requests\UserManagement\StoreUserRequest;
use App\Http\Requests\UserManagement\UpdateUserRequest;
use App\Http\Resources\UserManagement\ManagedUserResource;
use App\Http\Resources\UserManagement\RoleOptionResource;
use App\Http\Resources\UserManagement\UserRowResource;
use App\Models\Role;
use App\Models\User;
use App\Settings\SystemSettings;
use App\Support\DataTable;
use App\Support\PreferenceOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(IndexUserRequest $request): Response
    {
        Gate::authorize('users.view');

        return Inertia::render('user-management/users/Index', [
            'roleOptions' => fn () => $this->roles(),
            'users' => fn () => DataTable::make(
                User::query()
                    ->select(['id', 'public_id', 'first_name', 'last_name', 'email', 'created_at'])
                    ->with('roles:id,name,display_name'),
                $request,
                UserRowResource::class,
            ),
        ]);
    }

    public function create(SystemSettings $settings): Response
    {
        Gate::authorize('users.create');

        return Inertia::render('user-management/users/Create', [
            'roles' => $this->roles(),
            'preferenceOptions' => PreferenceOptions::forForms(),
            'defaultPreferences' => PreferenceOptions::defaults($settings),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create($request->safe()->only(['first_name', 'last_name', 'email', 'password']));
        $user->preferences()->create($request->safe()->only(PreferenceOptions::FIELDS));
        $user->syncRoles($request->validated('roles', []));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User created.')]);

        return to_route('user-management.users.index');
    }

    public function edit(User $user, SystemSettings $settings): Response
    {
        Gate::authorize('users.edit');

        abort_if($user->hasRole('admin') && ! $user->is(auth()->user()), 403, __('Only the administrator can edit their own account.'));

        $user->loadMissing(['preferences', 'roles']);

        return Inertia::render('user-management/users/Edit', [
            'managedUser' => (new ManagedUserResource($user, $settings))->resolve(),
            'roles' => $this->roles(),
            'preferenceOptions' => PreferenceOptions::forForms(),
            'defaultPreferences' => PreferenceOptions::defaults($settings),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $user->fill($request->safe()->only(['first_name', 'last_name', 'email']));

        if ($request->filled('password')) {
            $user->password = $request->validated('password');
        }

        $user->save();
        $user->preferences()->updateOrCreate(
            ['user_id' => $user->id],
            $request->safe()->only(PreferenceOptions::FIELDS),
        );

        if (! $user->hasRole('admin')) {
            $user->syncRoles($request->validated('roles', []));
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User updated.')]);

        return to_route('user-management.users.index');
    }

    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize('users.delete');

        abort_if($user->hasRole('admin'), 422, __('The administrator user cannot be deleted.'));

        abort_if($user->is(auth()->user()), 422, __('You cannot delete your own account here.'));

        $user->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User deleted.')]);

        return to_route('user-management.users.index');
    }

    /**
     * @return array<int, array{name: string, display_name: string}>
     */
    private function roles(): array
    {
        return Role::query()
            ->orderBy('display_name')
            ->get(['name', 'display_name'])
            ->toResourceCollection(RoleOptionResource::class)
            ->resolve();
    }
}
