<?php

namespace App\Services\Client\Auth;

use App\Models\Client;

final class ClientAuthSessionState
{
    public function setPendingRegistration(Client $client): void
    {
        session([
            'pending_client_id' => $client->user_id,
            'pending_gmail' => $client->gmail,
        ]);
    }

    public function pendingRegistrationClientId(): ?int
    {
        $id = session('pending_client_id');

        return $id !== null ? (int) $id : null;
    }

    public function pendingRegistrationEmail(): ?string
    {
        $email = session('pending_gmail');

        return is_string($email) && $email !== '' ? $email : null;
    }

    public function clearPendingRegistration(): void
    {
        session()->forget([
            'pending_client_id',
            'pending_gmail',
        ]);
    }

    public function setPendingRecovery(Client $client): void
    {
        session([
            'pending_recovery_id' => $client->user_id,
            'pending_recovery_gmail' => $client->gmail,
        ]);
        session()->forget('recovery_code_verified');
    }

    public function syncPendingRecovery(Client $client): void
    {
        session([
            'pending_recovery_id' => $client->user_id,
            'pending_recovery_gmail' => $client->gmail,
        ]);
    }

    public function clearPendingRecovery(): void
    {
        session()->forget([
            'pending_recovery_id',
            'pending_recovery_gmail',
            'recovery_code_verified',
        ]);
    }

    public function markRecoveryCodeVerified(): void
    {
        session(['recovery_code_verified' => true]);
    }

    public function isRecoveryCodeVerified(): bool
    {
        return (bool) session('recovery_code_verified');
    }

    /**
     * Resolves the pending recovery client from session, self-healing when
     * pending_recovery_id is stale but pending_recovery_gmail still matches.
     */
    public function resolvePendingRecoveryClient(): ?Client
    {
        $clientId = session('pending_recovery_id');
        $gmail = session('pending_recovery_gmail');

        if ($clientId) {
            $client = Client::find($clientId);
            if ($client) {
                return $client;
            }
        }

        if (is_string($gmail) && $gmail !== '') {
            return Client::where('gmail', strtolower($gmail))->first();
        }

        return null;
    }

    public function setAuthenticatedClientSession(Client $client): void
    {
        session([
            'client_id' => $client->user_id,
            'client_name' => $client->name,
            'client_first_surname' => $client->first_surname,
            'client_second_surname' => $client->second_surname,
        ]);
    }

    public function syncProfileSessionFromRequest(string $name, string $firstSurname, ?string $secondSurname): void
    {
        session([
            'client_name' => $name,
            'client_first_surname' => $firstSurname,
            'client_second_surname' => $secondSurname,
        ]);
    }

    /** Public display name for welcome toasts (name + surnames, or email). */
    public function welcomeDisplayName(Client $client): string
    {
        $parts = array_filter(array_map('trim', [
            (string) ($client->name ?? ''),
            (string) ($client->first_surname ?? ''),
            (string) ($client->second_surname ?? ''),
        ]), static fn (string $part): bool => $part !== '' && $part !== '-');

        if ($parts !== []) {
            return implode(' ', $parts);
        }

        $email = trim((string) ($client->gmail ?? ''));

        return $email !== '' ? $email : 'Usuario';
    }
}
