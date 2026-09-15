<?php

namespace App\Http\Controllers;

use App\Enums\ContactImportStatus;
use App\Http\Resources\Campaign\CampaignResource;
use App\Models\Campaigns\OnceOffCampaign;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OnceOffCampaignController extends Controller
{
    public function show(OnceOffCampaign $campaign): Response
    {
        Gate::authorize('campaigns.view');

        return Inertia::render('campaigns/once-off/Show', [
            'campaign' => $campaign->loadMissing('firstOnceOffSchedule')->toResource(CampaignResource::class)->resolve(),
            'contactSummary' => fn () => [
                'total' => $campaign->contacts()->count(),
                'pending_imports' => $campaign->contactImports()->where('status', '!=', ContactImportStatus::Synced)->count(),
            ],
        ]);
    }
}
