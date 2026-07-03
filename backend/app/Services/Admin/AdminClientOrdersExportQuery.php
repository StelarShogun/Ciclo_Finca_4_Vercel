<?php

namespace App\Services\Admin;

use Illuminate\Http\Request;

/** Filtros de Encargos (pedidos web) → exportaciones y descargas CSV/PDF. */
final class AdminClientOrdersExportQuery
{
    /** @var list<string> */
    public const QUERY_KEYS = ['status', 'search'];

    public static function queryStringFromRequest(Request $request): string
    {
        $params = array_filter(
            $request->only(self::QUERY_KEYS),
            static fn ($v) => $v !== null && $v !== ''
        );

        return count($params) ? '?'.http_build_query($params) : '';
    }
}
