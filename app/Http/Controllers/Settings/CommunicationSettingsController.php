<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateCommunicationProviderRequest;
use App\Http\Resources\CommunicationProviderResource;
use App\Models\CommunicationChannel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CommunicationSettingsController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('communication.view');
        $channels = [];
        foreach (CommunicationChannel::query()->with('providers.defaultAccount')->orderBy('position')->get() as $channel) {
            $providers = $channel->providers->sortBy([
                fn ($a, $b) => ($a->defaultAccount->priority ?? 999) <=> ($b->defaultAccount->priority ?? 999),
                ['position', 'asc'], ['id', 'asc'],
            ])->values();
            $channels[] = [
                'key' => $channel->code,
                'name' => $channel->name,
                'providers' => CommunicationProviderResource::collection($providers)->resolve(),
            ];
        }

        return Inertia::render('communication/Index', ['channels' => $channels]);
    }

    public function update(UpdateCommunicationProviderRequest $request, string $channel, string $provider): RedirectResponse
    {
        $model = $request->communicationProvider();
        $data = $request->validated();
        DB::transaction(function () use ($model, $data): void {
            $account = $model->defaultAccount()->lockForUpdate()->firstOrFail();
            $credentials = $data['credentials'];
            foreach ($model->fields as $field) {
                if ($field['secret'] && blank($credentials[$field['key']] ?? null)) {
                    $credentials[$field['key']] = $account->credentials[$field['key']] ?? null;
                }
            }
            $account->fill([...$data, 'credentials' => $credentials])->save();
        });
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Communication provider updated.')]);

        return to_route('communication.index');
    }
}
