<?php

namespace App\Http\Controllers\Admin\Products;

use App\Http\Controllers\Controller;
use App\Services\Admin\Products\ProductPayloadBuilder;
use Illuminate\Http\Request;
use Inertia\Inertia;

final class ProductInventoryController extends Controller
{
    public function index(Request $request, ProductPayloadBuilder $payloads)
    {
        return Inertia::render('Admin/Inventory/Index', $payloads->inventoryIndex($request));
    }
}
