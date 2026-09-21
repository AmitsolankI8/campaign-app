<?php

namespace App\Http\Controllers;

use App\Enums\ContactUploadMode;
use App\Http\Requests\Campaign\IndexOnceOffCampaignContactRequest;
use App\Http\Requests\Campaign\StoreOnceOffCampaignContactRequest;
use App\Http\Resources\Campaign\CampaignResource;
use App\Http\Resources\Campaign\OnceOffCampaignContactDetailsResource;
use App\Http\Resources\Campaign\OnceOffCampaignContactResource;
use App\Models\Campaigns\OnceOffCampaign;
use App\Models\OnceOffCampaignContact;
use App\Support\DataTable;
use App\Support\StageCampaignContactUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OnceOffCampaignContactController extends Controller
{
    public function index(Request $request, OnceOffCampaign $campaign): Response
    {
        Gate::authorize('campaigns.view');

        return Inertia::render('campaigns/once-off/Contacts', [
            'campaign' => $campaign->loadMissing('firstOnceOffSchedule')->toResource(CampaignResource::class)->resolve(),
            'contacts' => fn () => DataTable::make(
                $campaign->contacts()->getQuery(),
                IndexOnceOffCampaignContactRequest::forTable($request, 'contacts'),
                OnceOffCampaignContactResource::class,
            ),
        ]);
    }

    public function store(StoreOnceOffCampaignContactRequest $request, OnceOffCampaign $campaign, StageCampaignContactUpload $stage): RedirectResponse
    {
        $values = Arr::only($request->validated(), ['first_name', 'last_name', 'number', 'email']);
        $upload = $stage->handle($campaign, $request->user(), ContactUploadMode::from((int) $request->validated('mode')), [[
            'last_name' => null, 'email' => null, ...$values, 'row_number' => 1,
            'normalized_number' => preg_replace('/\D/', '', $values['number']),
        ]]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Contact staged. Review and sync it when ready.')]);

        return to_route('campaigns.once-off.contact-imports.show', ['campaign' => $campaign, 'contactImport' => $upload]);
    }

    public function show(Request $request, OnceOffCampaign $campaign, OnceOffCampaignContact $contact): JsonResponse
    {
        Gate::authorize('campaigns.view');
        abort_unless($contact->campaign_id === $campaign->id, 404);

        $contact->load([
            'campaign.schedules' => fn ($query) => $query
                ->with('channel')
                ->orderBy('attempt_number'),
            'communications' => fn ($query) => $query
                ->where('campaign_id', $campaign->id)
                ->with([
                    'scheduledCommunication',
                    'channel',
                    'attempts' => fn ($query) => $query
                        ->select(['id', 'public_id', 'communication_id', 'provider_account_id', 'attempt_number', 'status', 'retryable', 'error_code', 'started_at', 'completed_at'])
                        ->with(['providerAccount:id,provider_id', 'providerAccount.provider:id,name']),
                ]),
        ]);

        return response()->json(
            $contact->toResource(OnceOffCampaignContactDetailsResource::class)->resolve($request),
        );
    }
}
