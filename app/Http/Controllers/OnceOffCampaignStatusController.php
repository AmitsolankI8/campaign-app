<?php

namespace App\Http\Controllers;

use App\Models\Campaigns\OnceOffCampaign;
use App\Support\ChangeOnceOffCampaignStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class OnceOffCampaignStatusController extends Controller
{
    public function launch(OnceOffCampaign $campaign, ChangeOnceOffCampaignStatus $change): RedirectResponse
    {
        Gate::authorize('campaigns.view');
        Gate::authorize('campaigns.edit');

        $change->launch($campaign);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Campaign launched.')]);

        return back();
    }

    public function pause(OnceOffCampaign $campaign, ChangeOnceOffCampaignStatus $change): RedirectResponse
    {
        Gate::authorize('campaigns.view');
        Gate::authorize('campaigns.edit');
        $change->pause($campaign);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Campaign paused.')]);

        return back();
    }

    public function resume(OnceOffCampaign $campaign, ChangeOnceOffCampaignStatus $change): RedirectResponse
    {
        Gate::authorize('campaigns.view');
        Gate::authorize('campaigns.edit');
        $change->resume($campaign);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Campaign resumed.')]);

        return back();
    }

    public function cancel(OnceOffCampaign $campaign, ChangeOnceOffCampaignStatus $change): RedirectResponse
    {
        Gate::authorize('campaigns.view');
        Gate::authorize('campaigns.edit');
        $change->cancel($campaign);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Campaign cancelled.')]);

        return back();
    }

    public function stop(OnceOffCampaign $campaign, ChangeOnceOffCampaignStatus $change): RedirectResponse
    {
        Gate::authorize('campaigns.view');
        Gate::authorize('campaigns.edit');

        $change->stop($campaign);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Campaign stopped and returned to draft.')]);

        return back();
    }
}
