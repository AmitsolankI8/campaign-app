<?php

namespace App\Http\Controllers;

use App\Enums\CampaignStatus;
use App\Enums\CampaignType;
use App\Enums\ContactImportStatus;
use App\Enums\ContactUploadMode;
use App\Enums\ContactUploadRowStatus;
use App\Http\Requests\Campaign\IndexContactUploadRowRequest;
use App\Http\Requests\Campaign\IndexOnceOffCampaignContactImportRequest;
use App\Http\Requests\Campaign\StoreOnceOffCampaignContactImportRequest;
use App\Http\Requests\Campaign\SyncContactUploadRequest;
use App\Http\Resources\Campaign\CampaignResource;
use App\Http\Resources\Campaign\ContactUploadRowResource;
use App\Http\Resources\Campaign\OnceOffCampaignContactImportResource;
use App\Models\Campaign;
use App\Models\OnceOffCampaignContact;
use App\Models\OnceOffCampaignContactImport;
use App\Models\OnceOffCampaignContactUploadRow;
use App\Support\CampaignContactFileReader;
use App\Support\CampaignContactSyncPlan;
use App\Support\DataTable;
use App\Support\StageCampaignContactUpload;
use App\Support\SyncOnceOffCampaignContactImport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OnceOffCampaignContactImportController extends Controller
{
    public function index(Request $request, Campaign $campaign): Response
    {
        $this->authorizeCampaign($campaign);

        return Inertia::render('campaigns/once-off/UploadContacts', [
            'campaign' => $campaign->loadMissing('firstOnceOffSchedule')->toResource(CampaignResource::class)->resolve(),
            'contactImports' => fn () => DataTable::make(
                $campaign->contactImports()->select(['id', 'public_id', 'file_name', 'file_path', 'source', 'mode', 'uploaded_by', 'removed_count', 'contact_count', 'status', 'created_at', 'synced_at'])
                    ->with('uploader')->withCount(['uploadedRows', 'uploadedRows as processed_count' => fn ($query) => $query->whereNotIn('status', [ContactUploadRowStatus::Pending, ContactUploadRowStatus::Failed])])->getQuery(),
                IndexOnceOffCampaignContactImportRequest::forTable($request, 'imports'),
                OnceOffCampaignContactImportResource::class,
            ),
            'importStatuses' => ContactImportStatus::options(),
            'uploadModes' => ContactUploadMode::options(),
        ]);
    }

    public function preview(StoreOnceOffCampaignContactImportRequest $request, Campaign $campaign, CampaignContactFileReader $reader): JsonResponse
    {
        $this->authorizeCampaign($campaign);
        if ($campaign->status !== CampaignStatus::Draft) {
            throw ValidationException::withMessages(['mode' => __('Contacts can only be uploaded while the campaign is draft.')]);
        }
        /** @var UploadedFile $file */
        $file = $request->file('file');
        $preview = $reader->preview($file);

        return response()->json(Arr::except($preview, 'contacts'));
    }

    public function store(StoreOnceOffCampaignContactImportRequest $request, Campaign $campaign, CampaignContactFileReader $reader, StageCampaignContactUpload $stage): RedirectResponse
    {
        $this->authorizeCampaign($campaign);
        if ($campaign->status !== CampaignStatus::Draft) {
            throw ValidationException::withMessages(['mode' => __('Contacts can only be uploaded while the campaign is draft.')]);
        }
        /** @var UploadedFile $file */
        $file = $request->file('file');
        // Revalidate the submitted file instead of trusting the earlier preview.
        $upload = $stage->handle($campaign, $request->user(), ContactUploadMode::from((int) $request->validated('mode')), $reader->read($file), $file);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Upload saved. Review and sync contacts when ready.')]);

        return to_route('campaigns.once-off.contact-imports.show', ['campaign' => $campaign, 'contactImport' => $upload]);
    }

    public function show(Request $request, Campaign $campaign, OnceOffCampaignContactImport $contactImport, CampaignContactSyncPlan $plan): Response
    {
        $this->authorizeCampaign($campaign);
        abort_unless($contactImport->campaign_id === $campaign->id, 404);
        $contactImport->load('uploader')->loadCount([
            'uploadedRows',
            'uploadedRows as processed_count' => fn ($query) => $query->whereNotIn('status', [ContactUploadRowStatus::Pending, ContactUploadRowStatus::Failed]),
        ]);

        return Inertia::render('campaigns/once-off/UploadShow', [
            'campaign' => $campaign->loadMissing('firstOnceOffSchedule')->toResource(CampaignResource::class)->resolve(),
            'upload' => $contactImport->toResource(OnceOffCampaignContactImportResource::class)->resolve($request),
            'rows' => fn () => DataTable::make(
                $contactImport->uploadedRows()->with('contactImport')->select((new OnceOffCampaignContactUploadRow)->getTable().'.*')->selectSub(
                    OnceOffCampaignContact::query()->where('campaign_id', $campaign->id)
                        ->whereColumn((new OnceOffCampaignContact)->qualifyColumn('normalized_number'), (new OnceOffCampaignContactUploadRow)->qualifyColumn('normalized_number'))
                        ->selectRaw('count(*)'),
                    'existing_count',
                )->getQuery(),
                IndexContactUploadRowRequest::forTable($request, 'rows'),
                ContactUploadRowResource::class,
            ),
            'syncPlan' => fn () => $contactImport->status === ContactImportStatus::Synced ? null : $plan->make($campaign, $contactImport),
            'rowStatuses' => ContactUploadRowStatus::options(),
        ]);
    }

    public function download(Campaign $campaign, OnceOffCampaignContactImport $contactImport): StreamedResponse
    {
        $this->authorizeCampaign($campaign);
        abort_unless($contactImport->campaign_id === $campaign->id && $contactImport->file_path !== null, 404);
        abort_unless(Storage::disk('local')->exists($contactImport->file_path), 404);

        return Storage::disk('local')->download($contactImport->file_path, $contactImport->file_name, ['X-Content-Type-Options' => 'nosniff']);
    }

    public function sync(SyncContactUploadRequest $request, Campaign $campaign, OnceOffCampaignContactImport $contactImport, SyncOnceOffCampaignContactImport $sync): RedirectResponse
    {
        $this->authorizeCampaign($campaign);
        $sync->handle($campaign, $contactImport, $request->validated('row_ids'), $request->validated('fingerprint'));
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sync finished. Review the row outcomes below.')]);

        return to_route('campaigns.once-off.contact-imports.show', ['campaign' => $campaign, 'contactImport' => $contactImport]);
    }

    private function authorizeCampaign(Campaign $campaign): void
    {
        Gate::authorize('campaigns.view');
        Gate::authorize('campaigns.edit');
        abort_unless($campaign->campaign_type === CampaignType::OnceOff, 404);
    }
}
