<?php

namespace App\Services\Admin;

use Illuminate\Http\Request;

/**
 * Query string used by /reports/exportaciones and /inventory/export/* so
 * catalog downloads match the same filters as the inventory list.
 */
final class AdminInventoryExportQuery
{
    /** @var list<string> */
    public const QUERY_KEYS = [
        'search',
        'subcategory_id',
        'parent_category_id',
        'category_id',
        'stock_status',
        'status',
        'classifications',
        'sort',
        'order',
    ];

    public static function queryStringFromRequest(Request $request): string
    {
        $params = array_filter(
            $request->only(self::QUERY_KEYS),
            static fn ($v) => $v !== null && $v !== ''
        );

        return count($params) ? '?'.http_build_query($params) : '';
    }
}
