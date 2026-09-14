<?php

namespace App\Http\Controllers;

use App\Enums\CampaignType;
use App\Http\Resources\Campaign\CampaignResource;
use App\Models\Campaign;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OnceOffCampaignScheduleController extends Controller
{
    public function show(Campaign $campaign): Response
    {
        Gate::authorize('campaigns.view');
        abort_unless($campaign->campaign_type === CampaignType::OnceOff, 404);

        return Inertia::render('campaigns/once-off/Schedule', [
            'campaign' => $campaign->toResource(CampaignResource::class)->resolve(),
        ]);
    }
}
