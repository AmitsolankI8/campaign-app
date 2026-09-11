<?php

namespace App\Http\Controllers;

use App\Enums\CampaignType;
use App\Http\Requests\Campaign\StoreOnceOffCampaignContactImportRequest;
use App\Models\Campaign;
use App\Models\OnceOffCampaignContactImport;
use App\Support\CampaignContactFileReader;
use App\Support\SyncOnceOffCampaignContactImport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;

class OnceOffCampaignContactImportController extends Controller
{
    public function store(StoreOnceOffCampaignContactImportRequest $request, Campaign $campaign, CampaignContactFileReader $reader): RedirectResponse
    {
        abort_unless($campaign->campaign_type === CampaignType::OnceOff, 404);

        /** @var UploadedFile $file */
        $file = $request->file('file');
        $rows = $reader->read($file);
        $campaign->contactImports()->create([
            'file_name' => Str::limit($file->getClientOriginalName(), 255, ''),
            'contact_count' => count($rows),
            'rows' => $rows,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('File added to pending imports. Sync it to add the contacts.')]);

        return to_route('campaigns.once-off.show', ['campaign' => $campaign, 'tab' => 'upload_contacts']);
    }

    public function sync(Campaign $campaign, OnceOffCampaignContactImport $contactImport, SyncOnceOffCampaignContactImport $sync): RedirectResponse
    {
        Gate::authorize('campaigns.view');
        Gate::authorize('campaigns.edit');
        abort_unless($campaign->campaign_type === CampaignType::OnceOff, 404);

        $sync->handle($campaign, $contactImport);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Import synced. Contacts are now available in the Contacts tab.')]);

        return to_route('campaigns.once-off.show', ['campaign' => $campaign, 'tab' => 'upload_contacts']);
    }
}
