<?php

namespace App\Http\Controllers;

use App\Enums\CampaignType;
use App\Http\Requests\Campaign\StoreOnceOffCampaignContactRequest;
use App\Models\Campaign;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class OnceOffCampaignContactController extends Controller
{
    public function store(StoreOnceOffCampaignContactRequest $request, Campaign $campaign): RedirectResponse
    {
        abort_unless($campaign->campaign_type === CampaignType::OnceOff, 404);

        $campaign->onceOffContacts()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Contact added.')]);

        return to_route('campaigns.once-off.show', ['campaign' => $campaign, 'tab' => 'contacts']);
    }
}
