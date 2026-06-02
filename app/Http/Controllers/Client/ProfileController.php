<?php

namespace App\Http\Controllers\Client;

use App\Actions\Client\Profile\UpdateClientPassword;
use App\Actions\Client\Profile\UpdateClientProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\Profile\UpdateClientPasswordRequest;
use App\Http\Requests\Client\Profile\UpdateClientProfileRequest;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class ProfileController extends Controller
{
    public function show(): Response
    {
        $client = Auth::guard('clients')->user();

        return Inertia::render('Client/Profile/Index', [
            'profile' => [
                'name' => $client->name,
                'first_surname' => $client->first_surname,
                'second_surname' => $client->second_surname ?? '',
                'gmail' => $client->gmail,
                'provider' => $client->provider ?? 'local',
            ],
            'isGoogleOnly' => $client->provider === 'google',
            'profileFlash' => [
                'profileUpdated' => (bool) session('profile_updated'),
                'passwordUpdated' => (bool) session('password_updated'),
                'passwordDefined' => (bool) session('password_defined'),
            ],
        ]);
    }

    public function update(UpdateClientProfileRequest $request, UpdateClientProfile $action)
    {
        return $action->handle($request);
    }

    public function updatePassword(UpdateClientPasswordRequest $request, UpdateClientPassword $action)
    {
        return $action->handle($request);
    }
}
