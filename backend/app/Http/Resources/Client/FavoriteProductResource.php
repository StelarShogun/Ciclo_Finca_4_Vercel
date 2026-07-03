<?php

namespace App\Http\Resources\Client;

use App\Models\FavoriteProduct;
use App\Services\Client\Favorites\ClientFavoriteFormatter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class FavoriteProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var FavoriteProduct $favorite */
        $favorite = $this->resource;

        return ClientFavoriteFormatter::fromFavorite($favorite) ?? [];
    }
}
