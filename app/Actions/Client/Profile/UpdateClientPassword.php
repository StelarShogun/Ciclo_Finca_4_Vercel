<?php

namespace App\Actions\Client\Profile;

use App\Http\Requests\Client\Profile\UpdateClientPasswordRequest;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

final class UpdateClientPassword
{
    public function handle(UpdateClientPasswordRequest $request): JsonResponse|RedirectResponse
    {
        /** @var Client $client */
        $client = Auth::guard('clients')->user();
        $isGoogle = $request->isGoogleOnlyAccount();

        if (! $isGoogle) {
            if (! Hash::check($request->string('current_password')->toString(), $client->password)) {
                $error = ['current_password' => ['La contraseña actual no es correcta.']];

                if (! $request->header('X-Inertia') && ($request->ajax() || $request->wantsJson())) {
                    return response()->json(['success' => false, 'errors' => $error], 422);
                }

                return back()->withErrors($error)->withInput();
            }

            if (Hash::check($request->string('new_password')->toString(), $client->password)) {
                $error = ['new_password' => ['La nueva contraseña no puede ser igual a la actual.']];

                if (! $request->header('X-Inertia') && ($request->ajax() || $request->wantsJson())) {
                    return response()->json(['success' => false, 'errors' => $error], 422);
                }

                return back()->withErrors($error)->withInput();
            }
        }

        $client->update([
            'password' => Hash::make($request->string('new_password')->toString()),
            'provider' => 'local',
        ]);

        $flashKey = $isGoogle ? 'password_defined' : 'password_updated';
        $message = $isGoogle
            ? 'Contraseña definida. Ya puedes iniciar sesión con tu correo y contraseña.'
            : 'Contraseña actualizada correctamente.';

        if (! $request->header('X-Inertia') && ($request->ajax() || $request->wantsJson())) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'provider_changed' => $isGoogle,
            ]);
        }

        return redirect()->route('clients.profile')->with($flashKey, true);
    }
}
