<?php

namespace App\Services\Admin;

use Illuminate\Http\Request;

/** Filtros de la pantalla Marcas → exportaciones (coincide con el campo de búsqueda por nombre). */
final class AdminBrandsCatalogExportQuery
{
    /** @var list<string> */
    public const QUERY_KEYS = ['name'];

    public static function queryStringFromRequest(Request $request): string
    {
        $params = array_filter(
            $request->only(self::QUERY_KEYS),
            static fn ($v) => $v !== null && $v !== ''
        );

        return count($params) ? '?'.http_build_query($params) : '';
    }
}
