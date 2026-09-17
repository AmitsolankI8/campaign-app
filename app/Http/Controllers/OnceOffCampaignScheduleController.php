<?php

namespace App\Http\Controllers;

use App\Http\Requests\Campaign\UpdateOnceOffCampaignScheduleRequest;
use App\Http\Resources\Campaign\CampaignResource;
use App\Http\Resources\Campaign\OnceOffCampaignScheduleResource;
use App\Models\Campaigns\OnceOffCampaign;
use App\Models\CommunicationChannel;
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
                $campaign->schedules()->with('channel')->orderBy('attempt_number')->get(),
            )->resolve(),
            'channels' => fn () => CommunicationChannel::query()
                ->where('is_active', true)->orderBy('position')->orderBy('code')
                ->get()->map(fn (CommunicationChannel $channel) => [
                    'value' => $channel->code,
                    'label' => $channel->name,
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
