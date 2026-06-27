<?php

namespace App\Actions\Client\Favorites;

use App\Http\Resources\Client\FavoriteProductResource;
use App\Models\FavoriteProduct;
use App\Services\Client\Inertia\ListPaginationPayload;
use App\Support\AdminPerPage;
use Illuminate\Http\Request;

final class ListFavoriteProducts
{
    /**
     * @return array<string,mixed>
     */
    public function handle(int $clientId, Request $request): array
    {
        $paginator = FavoriteProduct::query()
            ->with(['product.category'])
            ->where('user_id', $clientId)
            ->latest('id')
            ->paginate(AdminPerPage::resolve($request->input('per_page', 10)))
            ->withQueryString();

        $favorites = FavoriteProductResource::collection(collect($paginator->items()))->resolve($request);

        return [
            'favorites' => $favorites,
            'pagination' => ListPaginationPayload::from($paginator),
            'json_pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ];
    }
}
