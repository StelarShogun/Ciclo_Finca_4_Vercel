<?php

namespace App\Actions\Client\Profile;

use App\Http\Requests\Client\Profile\UpdateClientAvatarRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class UpdateClientAvatar
{
    public function handle(UpdateClientAvatarRequest $request): RedirectResponse
    {
        $client = Auth::guard('clients')->user();

        $previous = (string) ($client->avatar_url ?? '');

        $path = $request->file('avatar')->store('avatars', 'public');

        $client->update([
            'avatar_url' => Storage::url($path),
        ]);

        // Solo se eliminan archivos subidos localmente; los avatares externos (Google) no tienen archivo.
        if (Str::startsWith($previous, '/storage/avatars/')) {
            Storage::disk('public')->delete(Str::after($previous, '/storage/'));
        }

        return redirect()->route('clients.profile')->with('avatar_updated', true);
    }
}
