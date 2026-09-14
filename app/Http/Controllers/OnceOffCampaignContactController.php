<?php

namespace App\Http\Controllers;

use App\Enums\CampaignType;
use App\Enums\ContactUploadMode;
use App\Http\Requests\Campaign\IndexOnceOffCampaignContactRequest;
use App\Http\Requests\Campaign\StoreOnceOffCampaignContactRequest;
use App\Http\Resources\Campaign\CampaignResource;
use App\Http\Resources\Campaign\OnceOffCampaignContactResource;
use App\Models\Campaign;
use App\Support\DataTable;
use App\Support\StageCampaignContactUpload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OnceOffCampaignContactController extends Controller
{
    public function index(Request $request, Campaign $campaign): Response
    {
        Gate::authorize('campaigns.view');
        abort_unless($campaign->campaign_type === CampaignType::OnceOff, 404);

        return Inertia::render('campaigns/once-off/Contacts', [
            'campaign' => $campaign->toResource(CampaignResource::class)->resolve(),
            'contacts' => fn () => DataTable::make(
                $campaign->onceOffContacts()->getQuery(),
                IndexOnceOffCampaignContactRequest::forTable($request, 'contacts'),
                OnceOffCampaignContactResource::class,
            ),
        ]);
    }

    public function store(StoreOnceOffCampaignContactRequest $request, Campaign $campaign, StageCampaignContactUpload $stage): RedirectResponse
    {
        abort_unless($campaign->campaign_type === CampaignType::OnceOff, 404);

        $values = Arr::only($request->validated(), ['first_name', 'last_name', 'number', 'email']);
        $upload = $stage->handle($campaign, $request->user(), ContactUploadMode::from((int) $request->validated('mode')), [[
            'last_name' => null, 'email' => null, ...$values, 'row_number' => 1,
            'normalized_number' => preg_replace('/\D/', '', $values['number']),
        ]]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Contact staged. Review and sync it when ready.')]);

        return to_route('campaigns.once-off.contact-imports.show', ['campaign' => $campaign, 'contactImport' => $upload]);
    }
}
