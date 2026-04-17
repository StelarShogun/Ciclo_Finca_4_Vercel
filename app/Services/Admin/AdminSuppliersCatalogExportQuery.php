<?php

namespace App\Services\Admin;

use Illuminate\Http\Request;

/** Filtros de la pantalla Proveedores (listado admin) → exportaciones. */
final class AdminSuppliersCatalogExportQuery
{
    /** @var list<string> */
    public const QUERY_KEYS = ['name', 'contact'];

    public static function queryStringFromRequest(Request $request): string
    {
        $params = array_filter(
            $request->only(self::QUERY_KEYS),
            static fn ($v) => $v !== null && $v !== ''
        );

        return count($params) ? '?'.http_build_query($params) : '';
    }
}
