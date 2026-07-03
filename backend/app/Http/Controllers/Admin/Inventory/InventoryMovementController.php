<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Inventory\InventoryMovementIndexRequest;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Services\Admin\Inventory\InventoryMovementQuery;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final class InventoryMovementController extends Controller
{
    public function index(InventoryMovementIndexRequest $request, InventoryMovementQuery $query)
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('viewAny', InventoryMovement::class);

        return Inertia::render('Admin/Reports/MovementsIndex', $query->indexPayload($request->validated()));
    }

    public function show(InventoryMovementIndexRequest $request, InventoryMovementQuery $query, int $productId)
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('viewAny', InventoryMovement::class);

        $product = Product::query()
            ->with(['category', 'supplier'])
            ->findOrFail($productId);

        return Inertia::render('Admin/Reports/MovementsShow', $query->showPayload($product));
    }

    public function json(InventoryMovementIndexRequest $request, InventoryMovementQuery $query, int $productId)
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('viewAny', InventoryMovement::class);

        $product = Product::query()->findOrFail($productId);

        return response()->json($query->jsonPayload($product, $request->validated()));
    }
}
