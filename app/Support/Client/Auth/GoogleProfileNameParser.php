<?php

namespace App\Support\Client\Auth;

final class GoogleProfileNameParser
{
    /**
     * Map Google userinfo to client_table name fields (first_surname is NOT NULL).
     *
     * @param  array<string, mixed>  $googleUser
     * @return array{name: string, first_surname: string, second_surname: ?string}
     */
    public function parse(array $googleUser): array
    {
        $given = trim((string) data_get($googleUser, 'given_name', ''));
        $family = trim((string) data_get($googleUser, 'family_name', ''));
        $full = trim((string) data_get($googleUser, 'name', ''));

        $nombre = 'Usuario';
        $apellido1 = null;
        $apellido2 = null;

        if ($given !== '') {
            $nombre = $given;
            if ($family !== '') {
                $familyParts = array_values(array_filter(preg_split('/\s+/u', $family) ?: []));
                $apellido1 = $familyParts[0] ?? null;
                $apellido2 = count($familyParts) > 1
                    ? implode(' ', array_slice($familyParts, 1))
                    : null;
            }
        } elseif ($full !== '') {
            $partes = array_values(array_filter(explode(' ', $full, 3)));
            $nombre = $partes[0] ?? $full;
            $apellido1 = $partes[1] ?? null;
            $apellido2 = $partes[2] ?? null;
        }

        if ($apellido1 === null || trim($apellido1) === '') {
            $apellido1 = '-';
        }

        return [
            'name' => $nombre,
            'first_surname' => $apellido1,
            'second_surname' => $apellido2,
        ];
    }
}
