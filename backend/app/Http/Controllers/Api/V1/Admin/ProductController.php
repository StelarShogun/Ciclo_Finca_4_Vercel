<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Admin\Products\ListProducts;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Productos admin para el SPA Next. La lista reusa ListProducts (mismo
 * paginador que el web index). El detalle y el CRUD se agregan en los
 * siguientes slices reusando ProductAdminPayloadService y las Actions.
 */
final class ProductController extends Controller
{
    public function index(Request $request, ListProducts $products): JsonResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('viewAny', Product::class);

        return response()->json($products->handle($request));
    }
}
