<?php

namespace App\Actions\Client\Profile;

use App\Http\Requests\Client\Profile\UpdateClientProfileRequest;
use App\Services\Client\Auth\ClientAuthSessionState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

final class UpdateClientProfile
{
    public function __construct(
        private ClientAuthSessionState $sessionState,
    ) {}

    public function handle(UpdateClientProfileRequest $request): JsonResponse|RedirectResponse
    {
        $client = Auth::guard('clients')->user();

        $client->update([
            'name' => $request->string('name')->toString(),
            'first_surname' => $request->string('first_surname')->toString(),
            'second_surname' => $request->input('second_surname'),
            'gmail' => $request->string('gmail')->toString(),
        ]);

        $this->sessionState->syncProfileSessionFromRequest(
            $request->string('name')->toString(),
            $request->string('first_surname')->toString(),
            $request->input('second_surname'),
        );

        if (! $request->header('X-Inertia') && ($request->ajax() || $request->wantsJson())) {
            return response()->json([
                'success' => true,
                'message' => 'Cambios guardados correctamente.',
            ]);
        }

        return redirect()->route('clients.profile')->with('profile_updated', true);
    }
}
