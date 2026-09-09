<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateSystemSettingsRequest;
use App\Settings\SystemSettings;
use App\Support\PreferenceOptions;
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
            'settings' => [
                'default_country_preference_id' => $settings->default_country_preference_id,
                'default_timezone_preference_id' => $settings->default_timezone_preference_id,
                'default_language_preference_id' => $settings->default_language_preference_id,
                'default_number_format_preference_id' => $settings->default_number_format_preference_id,
                'default_date_format_preference_id' => $settings->default_date_format_preference_id,
                'default_time_format_preference_id' => $settings->default_time_format_preference_id,
            ],
            'preferenceOptions' => PreferenceOptions::forForms(),
        ]);
    }

    public function update(UpdateSystemSettingsRequest $request, SystemSettings $settings): RedirectResponse
    {
        $settings->fill($request->validated())->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('System settings updated.')]);

        return to_route('system-settings.edit');
    }
}
