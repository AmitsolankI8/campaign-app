<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Settings\SystemSettings;
use App\Support\PreferenceOptions;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PreferencesController extends Controller
{
    public function index(SystemSettings $settings): Response
    {
        Gate::authorize('preferences.view');

        return Inertia::render('system-settings/Preferences', [
            'preferences' => PreferenceOptions::forIndex(),
            'defaultPreferences' => PreferenceOptions::defaults($settings),
        ]);
    }
}
