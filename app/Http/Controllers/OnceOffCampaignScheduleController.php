<?php

namespace App\Http\Controllers;

use App\Http\Requests\Campaign\UpdateOnceOffCampaignScheduleRequest;
use App\Http\Resources\Campaign\CampaignResource;
use App\Http\Resources\Campaign\OnceOffCampaignScheduleResource;
use App\Models\Campaigns\OnceOffCampaign;
use App\Models\CommunicationProvider;
use App\Support\SaveOnceOffCampaignSchedules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OnceOffCampaignScheduleController extends Controller
{
    public function show(OnceOffCampaign $campaign): Response
    {
        Gate::authorize('campaigns.view');

        return Inertia::render('campaigns/once-off/Schedule', [
            'campaign' => $campaign->loadMissing('firstOnceOffSchedule')->toResource(CampaignResource::class)->resolve(),
            'schedules' => fn () => OnceOffCampaignScheduleResource::collection(
                $campaign->schedules()->orderBy('attempt_count')->get(),
            )->resolve(),
            'channels' => fn () => CommunicationProvider::query()
                ->select(['channel', 'channel_name', 'channel_position'])
                ->orderBy('channel_position')->orderBy('channel')
                ->get()->unique('channel')->map(fn (CommunicationProvider $provider) => [
                    'value' => $provider->channel,
                    'label' => $provider->channel_name,
                ])->values()->all(),
        ]);
    }

    public function update(UpdateOnceOffCampaignScheduleRequest $request, OnceOffCampaign $campaign, SaveOnceOffCampaignSchedules $save): RedirectResponse
    {
        $save->handle($campaign, $request->validated('schedules'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Campaign schedule saved.')]);

        return to_route('campaigns.once-off.schedule.show', $campaign);
    }
}
