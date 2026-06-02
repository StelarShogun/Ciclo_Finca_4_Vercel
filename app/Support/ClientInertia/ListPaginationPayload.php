<?php

namespace App\Support\ClientInertia;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Shared Inertia pagination shape for client list pages (invoices, notifications, favorites).
 */
final class ListPaginationPayload
{
    /**
     * @return array{
     *     currentPage: int,
     *     lastPage: int,
     *     perPage: int,
     *     total: int,
     *     from: int|null,
     *     to: int|null,
     *     links: list<array{url: string|null, label: string, active: bool, page: int|null}>
     * }
     */
    public static function from(LengthAwarePaginator $paginator): array
    {
        return [
            'currentPage' => (int) $paginator->currentPage(),
            'lastPage' => (int) $paginator->lastPage(),
            'perPage' => (int) $paginator->perPage(),
            'total' => (int) $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'links' => collect($paginator->linkCollection())->map(fn (array $link): array => [
                'url' => $link['url'],
                'label' => (string) $link['label'],
                'active' => (bool) $link['active'],
                'page' => $link['page'] ?? null,
            ])->values()->all(),
        ];
    }
}
