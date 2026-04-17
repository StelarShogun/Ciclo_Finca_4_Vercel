<?php

namespace App\Services\Admin;

use Illuminate\Http\Request;

/** Filtros de la pantalla Pedidos a proveedores → exportaciones y descargas CSV/PDF. */
final class AdminSupplierOrdersExportQuery
{
    /** @var list<string> */
    public const QUERY_KEYS = ['state', 'search', 'date_from', 'date_to'];

    public static function queryStringFromRequest(Request $request): string
    {
        $params = array_filter(
            $request->only(self::QUERY_KEYS),
            static fn ($v) => $v !== null && $v !== ''
        );

        return count($params) ? '?'.http_build_query($params) : '';
    }
}
