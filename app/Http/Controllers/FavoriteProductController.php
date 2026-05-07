<?php

namespace App\Http\Controllers;

use App\Models\FavoriteProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class FavoriteProductController extends Controller
{
    public function index()
    {
        if (! Schema::hasTable('favorite_products')) {
            return response()->json([
                'success' => false,
                'message' => 'La tabla de favoritos no existe. Ejecuta las migraciones.',
            ], 500);
        }

        $clientId = (int) Auth::guard('clients')->id();

        $favorites = FavoriteProduct::query()
            ->with(['product.category'])
            ->where('user_id', $clientId)
            ->latest('id')
            ->get()
            ->filter(fn (FavoriteProduct $favorite) => $favorite->product !== null)
            ->map(function (FavoriteProduct $favorite) {
                $product = $favorite->product;

                return [
                    'product_id' => (int) $product->product_id,
                    'name' => (string) $product->name,
                    'category' => (string) ($product->category->name ?? 'Sin categoría'),
                    'price' => (float) $product->sale_price,
                    'price_formatted' => '₡'.number_format((float) $product->sale_price, 0, ',', '.'),
                    'stock_label' => (string) $product->clientCatalogStockLabel(),
                    'url' => (string) $product->clientProductUrl(),
                    'image_url' => (string) ($product->getFirstMediaUrl('main_image')
                        ?: asset('assets/images/products/'.($product->image ?? 'default.png'))),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'favorites' => $favorites,
        ]);
    }

    public function toggle(Request $request)
    {
        if (! Schema::hasTable('favorite_products')) {
            return response()->json([
                'success' => false,
                'message' => 'La tabla de favoritos no existe. Ejecuta las migraciones.',
            ], 500);
        }

        $request->validate([
            'product_id' => 'required|exists:products,product_id',
        ]);

        $clientId = (int) Auth::guard('clients')->id();
        $productId = (int) $request->input('product_id');

        $favorite = FavoriteProduct::query()
            ->where('user_id', $clientId)
            ->where('product_id', $productId)
            ->first();

        try {
            if ($favorite) {
                $favorite->delete();

                return response()->json([
                    'success' => true,
                    'is_favorite' => false,
                ]);
            }

            FavoriteProduct::create([
                'user_id' => $clientId,
                'product_id' => $productId,
            ]);

            return response()->json([
                'success' => true,
                'is_favorite' => true,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo guardar el favorito en este momento.',
            ], 500);
        }
    }
}
