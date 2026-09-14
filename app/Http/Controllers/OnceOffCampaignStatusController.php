<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Support\ChangeOnceOffCampaignStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class OnceOffCampaignStatusController extends Controller
{
    public function launch(Campaign $campaign, ChangeOnceOffCampaignStatus $change): RedirectResponse
    {
        Gate::authorize('campaigns.view');
        Gate::authorize('campaigns.edit');

        $change->launch($campaign);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Campaign launched.')]);

        return back();
    }

    public function stop(Campaign $campaign, ChangeOnceOffCampaignStatus $change): RedirectResponse
    {
        Gate::authorize('campaigns.view');
        Gate::authorize('campaigns.edit');

        $change->stop($campaign);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Campaign stopped and returned to draft.')]);

        return back();
    }
}
