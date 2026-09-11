<?php

namespace App\Http\Controllers;

use App\Enums\CampaignType;
use App\Enums\ContactImportStatus;
use App\Http\Requests\Campaign\IndexOnceOffCampaignContactImportRequest;
use App\Http\Requests\Campaign\IndexOnceOffCampaignContactRequest;
use App\Http\Resources\Campaign\CampaignResource;
use App\Http\Resources\Campaign\OnceOffCampaignContactImportResource;
use App\Http\Resources\Campaign\OnceOffCampaignContactResource;
use App\Models\Campaign;
use App\Support\DataTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OnceOffCampaignController extends Controller
{
    public function show(Request $request, Campaign $campaign): Response
    {
        Gate::authorize('campaigns.view');

        abort_unless($campaign->campaign_type === CampaignType::OnceOff, 404);

        return Inertia::render('campaigns/once-off/Show', [
            'campaign' => $campaign->toResource(CampaignResource::class)->resolve(),
            'contacts' => fn () => DataTable::make(
                $campaign->onceOffContacts()->getQuery(),
                IndexOnceOffCampaignContactRequest::forTable($request, 'contacts'),
                OnceOffCampaignContactResource::class,
            ),
            'contactImports' => fn () => Gate::allows('campaigns.edit') ? DataTable::make(
                $campaign->contactImports()->select(['id', 'public_id', 'file_name', 'contact_count', 'status', 'created_at', 'synced_at'])->getQuery(),
                IndexOnceOffCampaignContactImportRequest::forTable($request, 'imports'),
                OnceOffCampaignContactImportResource::class,
            ) : null,
            'contactSummary' => fn () => [
                'total' => $campaign->onceOffContacts()->count(),
                'pending_imports' => $campaign->contactImports()->where('status', ContactImportStatus::Pending)->count(),
            ],
            'importStatuses' => fn () => ContactImportStatus::options(),
        ]);
    }
}
