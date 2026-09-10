<?php

namespace App\Http\Controllers;

use App\Enums\CampaignStatus;
use App\Enums\CampaignType;
use App\Http\Requests\Campaign\IndexCampaignRequest;
use App\Http\Requests\Campaign\StoreCampaignRequest;
use App\Http\Requests\Campaign\UpdateCampaignRequest;
use App\Http\Resources\Campaign\CampaignResource;
use App\Http\Resources\Campaign\CampaignRowResource;
use App\Models\Campaign;
use App\Support\DataTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CampaignController extends Controller
{
    public function index(IndexCampaignRequest $request): Response
    {
        Gate::authorize('campaigns.view');

        return Inertia::render('campaigns/Index', [
            'campaignTypes' => fn () => CampaignType::options(),
            'campaignStatuses' => fn () => CampaignStatus::options(),
            'campaigns' => fn () => DataTable::make(
                Campaign::query()->select(['id', 'public_id', 'name', 'campaign_type', 'status', 'short_note', 'created_at']),
                $request,
                CampaignRowResource::class,
            ),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('campaigns.create');

        return Inertia::render('campaigns/Create', [
            'campaignTypes' => CampaignType::options(),
        ]);
    }

    public function store(StoreCampaignRequest $request): RedirectResponse
    {
        $campaign = Campaign::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Campaign created.')]);

        return to_route($campaign->campaign_type->showRouteName(), $campaign);
    }

    public function show(Campaign $campaign): RedirectResponse
    {
        Gate::authorize('campaigns.view');

        return to_route($campaign->campaign_type->showRouteName(), $campaign);
    }

    public function edit(Campaign $campaign): Response
    {
        Gate::authorize('campaigns.edit');

        return Inertia::render('campaigns/Edit', [
            'campaign' => $campaign->toResource(CampaignResource::class)->resolve(),
        ]);
    }

    public function update(UpdateCampaignRequest $request, Campaign $campaign): RedirectResponse
    {
        $campaign->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Campaign updated.')]);

        return to_route($campaign->campaign_type->showRouteName(), $campaign);
    }
}
