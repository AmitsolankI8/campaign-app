<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateSystemSettingsRequest;
use App\Settings\SystemSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SystemSettingsController extends Controller
{
    public function edit(SystemSettings $settings): Response
    {
        Gate::authorize('system-settings.view');

        return Inertia::render('system-settings/System', [
            'settings' => ['display_name' => $settings->display_name],
        ]);
    }

    public function update(UpdateSystemSettingsRequest $request, SystemSettings $settings): RedirectResponse
    {
        $settings->fill($request->validated())->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('System settings updated.')]);

        return to_route('system-settings.edit');
    }
}
