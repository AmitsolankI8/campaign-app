<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateCommunicationProviderRequest;
use App\Http\Resources\CommunicationProviderResource;
use App\Models\CommunicationProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CommunicationSettingsController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('communication.view');
        $providersByChannel = CommunicationProvider::query()
            ->orderBy('channel_position')->orderBy('priority')->orderBy('position')->orderBy('id')
            ->get()->groupBy('channel');
        $channels = [];
        foreach ($providersByChannel as $channel => $providers) {
            $channels[] = [
                'key' => $channel,
                'name' => $providers->first()->channel_name,
                'providers' => CommunicationProviderResource::collection($providers)->resolve(),
            ];
        }

        return Inertia::render('communication/Index', ['channels' => $channels]);
    }

    public function update(UpdateCommunicationProviderRequest $request, string $channel, string $provider): RedirectResponse
    {
        $model = $request->communicationProvider();
        $data = $request->validated();
        $credentials = $data['credentials'];
        foreach ($model->fields as $field) {
            if ($field['secret'] && blank($credentials[$field['key']] ?? null)) {
                $credentials[$field['key']] = $model->credentials[$field['key']] ?? null;
            }
        }
        $model->fill([...$data, 'credentials' => $credentials])->save();
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Communication provider updated.')]);

        return to_route('communication.index');
    }
}
