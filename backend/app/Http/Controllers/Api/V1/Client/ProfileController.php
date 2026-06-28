<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Actions\Client\Profile\UpdateClientPassword;
use App\Actions\Client\Profile\UpdateClientProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\Profile\UpdateClientPasswordRequest;
use App\Http\Requests\Client\Profile\UpdateClientProfileRequest;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Perfil del cliente para el SPA Next. Reusa las Actions (que devuelven JSON
 * cuando wantsJson). El avatar (upload) se atiende en la ruta web por ahora.
 */
final class ProfileController extends Controller
{
    public function show(): JsonResponse
    {
        /** @var Client $client */
        $client = Auth::guard('clients')->user();
        Gate::forUser($client)->authorize('profile.view', $client);

        return response()->json(['data' => [
            'name' => $client->name,
            'first_surname' => $client->first_surname,
            'second_surname' => $client->second_surname ?? '',
            'gmail' => $client->gmail,
            'provider' => $client->provider ?? 'local',
            'avatar_url' => $client->avatar_url,
            'isGoogleOnly' => $client->provider === 'google',
        ]]);
    }

    public function update(UpdateClientProfileRequest $request, UpdateClientProfile $action): JsonResponse
    {
        $client = Auth::guard('clients')->user();
        Gate::forUser($client)->authorize('profile.update', $client);

        $response = $action->handle($request);

        return $response instanceof JsonResponse ? $response : response()->json(['success' => true]);
    }

    public function updatePassword(UpdateClientPasswordRequest $request, UpdateClientPassword $action): JsonResponse
    {
        $client = Auth::guard('clients')->user();
        Gate::forUser($client)->authorize('profile.update', $client);

        $response = $action->handle($request);

        return $response instanceof JsonResponse ? $response : response()->json(['success' => true]);
    }
}
