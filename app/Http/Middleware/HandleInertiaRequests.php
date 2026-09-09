<?php

namespace App\Http\Middleware;

use App\Support\PermissionRegistry;
use App\Support\UserPreferences;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => fn () => config('app.name', 'Laravel'),
            'auth' => [
                'user' => $request->user()?->loadMissing('roles'),
                'preferences' => fn () => UserPreferences::forUser($request->user()),
                'permissions' => fn () => array_values(array_filter(
                    PermissionRegistry::names(),
                    fn (string $permission) => $request->user()?->can($permission) ?? false,
                )),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
