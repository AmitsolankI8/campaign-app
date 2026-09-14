<?php

namespace App\Http\Controllers;

use App\Enums\CampaignType;
use App\Http\Requests\Campaign\UpdateOnceOffCampaignScheduleRequest;
use App\Http\Resources\Campaign\CampaignResource;
use App\Http\Resources\Campaign\OnceOffCampaignScheduleResource;
use App\Models\Campaign;
use App\Models\CommunicationProvider;
use App\Support\SaveOnceOffCampaignSchedules;
use Illuminate\Http\RedirectResponse;
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
            'campaign' => $campaign->loadMissing('firstOnceOffSchedule')->toResource(CampaignResource::class)->resolve(),
            'schedules' => fn () => OnceOffCampaignScheduleResource::collection(
                $campaign->onceOffSchedules()->orderBy('attempt_count')->get(),
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

    public function update(UpdateOnceOffCampaignScheduleRequest $request, Campaign $campaign, SaveOnceOffCampaignSchedules $save): RedirectResponse
    {
        $save->handle($campaign, $request->validated('schedules'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Campaign schedule saved.')]);

        return to_route('campaigns.once-off.schedule.show', $campaign);
    }
}
