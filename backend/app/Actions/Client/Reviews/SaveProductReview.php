<?php

namespace App\Actions\Client\Reviews;

use App\Models\Client;
use App\Models\ProductReview;
use App\Models\SaleItem;
use Illuminate\Auth\Access\AuthorizationException;

final class SaveProductReview
{
    public function handle(Client $client, int $productId, int $stars): ProductReview
    {
        if (! $this->clientPurchasedProduct($client, $productId)) {
            throw new AuthorizationException('Solo puedes reseñar productos que hayas comprado y retirado.');
        }

        return ProductReview::query()->updateOrCreate(
            [
                'product_id' => $productId,
                'client_id' => $client->user_id,
            ],
            [
                'stars' => $stars,
            ]
        );
    }

    private function clientPurchasedProduct(Client $client, int $productId): bool
    {
        return SaleItem::query()
            ->where('product_id', $productId)
            ->whereHas('sale', function ($q) use ($client) {
                $q->where('client_id', $client->user_id)
                    ->where('status', 'completed');
            })
            ->exists();
    }
}
