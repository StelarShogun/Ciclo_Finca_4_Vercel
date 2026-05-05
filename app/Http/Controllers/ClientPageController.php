<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Client;
use App\Models\FavoriteProduct;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\InventoryMovementService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class ClientPageController extends Controller
{
    public function home()
    {
        // Load featured products that are active, in stock, and recently created.
        $featuredProducts = Product::with(['category'])
            ->activeInClientStore()
            ->where('is_featured', true)
            ->where('stock_current', '>', 0)
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        $categories = Category::whereNull('parent_category_id')
            ->with(['childCategories' => function ($q) {
                $q->orderBy('name');
            }])
            ->orderBy('name')
            ->get();

        $cartCount = $this->getCartCount();

        return view('client.home', compact('featuredProducts', 'categories', 'cartCount'));
    }

    public function catalog(Request $request)
    {
        // Base del catálogo cliente: solo productos visibles/publicables para el cliente.
        $query = Product::with(['category', 'brands'])->activeInClientStore();

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('description', 'like', '%' . $searchTerm . '%');
            });
        }

        // Filter by brand when brand_id is present in the request.
        $selectedBrand = null;
        if ($request->filled('brand_id')) {
            $brandId = (int) $request->brand_id;
            $selectedBrand = Brand::find($brandId);
            if ($selectedBrand) {
                $query->whereHas('brands', fn($q) => $q->where('brands.id', $brandId));
            } else {
                // Brand does not exist — force empty result set (CA-03).
                $query->whereRaw('1 = 0');
            }
        }

        $selectedCategory = null;
        $subcategories = collect();
        $parentCategoryForSubcats = null;

        if ($request->filled('category_id')) {
            $selectedCategory = Category::find((int) $request->category_id);
            if ($selectedCategory) {
                // Include child categories when a parent category is selected.
                if (is_null($selectedCategory->parent_category_id)) {
                    $childIds = Category::where('parent_category_id', $selectedCategory->category_id)->pluck('category_id');
                    $query->where(function ($q) use ($selectedCategory, $childIds) {
                        $q->where('category_id', $selectedCategory->category_id)
                            ->orWhereIn('category_id', $childIds);
                    });
                    $subcategories = Category::where('parent_category_id', $selectedCategory->category_id)->orderBy('name')->get();
                    $parentCategoryForSubcats = $selectedCategory;
                } else {
                    $query->where('category_id', $selectedCategory->category_id);
                    $parentCategoryForSubcats = $selectedCategory->parent()->first();
                    if ($parentCategoryForSubcats instanceof Category) {
                        $subcategories = Category::where('parent_category_id', $parentCategoryForSubcats->category_id)->orderBy('name')->get();
                    }
                }
            }
        }

        $minPrice = $request->filled('min_price') ? $request->input('min_price') : null;
        $maxPrice = $request->filled('max_price') ? $request->input('max_price') : null;

        // Reject invalid price ranges before applying filters.
        if (is_numeric($minPrice) && is_numeric($maxPrice) && (float) $minPrice > (float) $maxPrice) {
            return redirect()->route('clients.catalog', $request->except(['min_price', 'max_price', 'page']))
                ->withInput()
                ->withErrors(['price_range' => 'El precio mínimo debe ser menor o igual al precio máximo.']);
        }

        if (is_numeric($minPrice) && is_numeric($maxPrice)) {
            $query->whereBetween('sale_price', [$minPrice, $maxPrice]);
        } elseif (is_numeric($minPrice)) {
            $query->where('sale_price', '>=', $minPrice);
        } elseif (is_numeric($maxPrice)) {
            $query->where('sale_price', '<=', $maxPrice);
        }

        $sort = $request->get('sort', 'created_at');
        $order = $request->get('direction', 'desc');

        match ($sort) {
            'price' => $query->orderBy('sale_price', $order),
            'name' => $query->orderBy('name', $order),
            default => $query->orderBy('created_at', $order),
        };

        $perPage = $request->get('per_page', 12);
        $products = $query->paginate($perPage)->withQueryString();

        $categories = Category::whereNull('parent_category_id')
            ->with(['childCategories' => function ($q) {
                $q->orderBy('name');
            }])
            ->orderBy('name')
            ->get();

        $brands = Brand::has('products')->orderBy('name')->get();

        $cartCount = $this->getCartCount();
        $catalogSpotlight = $this->catalogSpotlightProductRows();
        $favoriteProductIds = collect();

        if (Auth::guard('clients')->check()) {
            $favoriteProductIds = FavoriteProduct::query()
                ->where('user_id', (int) Auth::guard('clients')->id())
                ->pluck('product_id')
                ->map(fn($id) => (int) $id);
        }

        $catalogParams = $request->except(['category_id', 'page']);
        $catalogCategoryNav = $this->buildCatalogCategoryNav($categories, $catalogParams);
        $emptyCategoryNoProducts = $request->filled('category_id')
            && $selectedCategory
            && $products->total() === 0;

        return view('client.catalog', compact(
            'products',
            'categories',
            'cartCount',
            'selectedCategory',
            'subcategories',
            'parentCategoryForSubcats',
            'catalogSpotlight',
            'favoriteProductIds',
            'catalogParams',
            'catalogCategoryNav',
            'emptyCategoryNoProducts',
            'brands',
            'selectedBrand'
        ));
    }

    /**
     * Árbol de categorías para el catálogo cliente (JSON + sidebar/panel). Sin columna extra en BD.
     *
     * @param  Collection<int, Category>  $rootCategories
     * @return array<int, array{id: int, name: string, icon: string, url_parent: string, children: array<int, array{id: int, name: string, url: string}>}>
     */
    private function buildCatalogCategoryNav($rootCategories, array $catalogParams): array
    {
        return $rootCategories->map(function (Category $c) use ($catalogParams) {
            return [
                'id' => (int) $c->category_id,
                'name' => $c->name,
                'icon' => $this->clientCatalogCategoryIconClass($c->name),
                'url_parent' => route('clients.catalog', array_merge($catalogParams, ['category_id' => $c->category_id])),
                'children' => $c->childCategories->map(function (Category $ch) use ($catalogParams) {
                    return [
                        'id' => (int) $ch->category_id,
                        'name' => $ch->name,
                        'url' => route('clients.catalog', array_merge($catalogParams, ['category_id' => $ch->category_id])),
                    ];
                })->values()->all(),
            ];
        })->values()->all();
    }

    /** Clases Font Awesome (fas fa-*) por heurística de nombre — sin icono en BD. */
    private function clientCatalogCategoryIconClass(?string $name): string
    {
        $n = mb_strtolower(trim((string) $name), 'UTF-8');
        $pairs = [
            'bicicleta' => 'fas fa-bicycle',
            'bici' => 'fas fa-bicycle',
            'accesorio' => 'fas fa-box-open',
            'componente' => 'fas fa-cogs',
            'herramienta' => 'fas fa-wrench',
            'nutrición' => 'fas fa-apple-alt',
            'nutricion' => 'fas fa-apple-alt',
            'ropa' => 'fas fa-tshirt',
            'seguridad' => 'fas fa-shield-alt',
            'repuesto' => 'fas fa-cog',
            'llanta' => 'fas fa-circle',
            'casco' => 'fas fa-hard-hat',
            'luz' => 'fas fa-lightbulb',
            'electr' => 'fas fa-bolt',
        ];
        foreach ($pairs as $needle => $icon) {
            if ($needle !== '' && str_contains($n, $needle)) {
                return $icon;
            }
        }

        return 'fas fa-layer-group';
    }

    // Returns spotlight rows using featured products first, then recent products.
    private function catalogSpotlightProductRows(): Collection
    {
        $maxTotal = 12;
        $maxFeatured = 8;

        $featured = Product::with(['category'])
            ->activeInClientStore()
            ->where('is_featured', true)
            ->orderByDesc('created_at')
            ->limit($maxFeatured)
            ->get();

        $featuredIds = $featured->pluck('product_id')->all();
        $remaining = max(0, $maxTotal - $featured->count());

        $novelties = $remaining > 0
            ? Product::with(['category'])
            ->activeInClientStore()
            ->whereNotIn('product_id', $featuredIds)
            ->orderByDesc('created_at')
            ->limit($remaining)
            ->get()
            : collect();

        return $featured->map(fn(Product $p) => ['product' => $p, 'spotlight' => 'featured'])
            ->concat($novelties->map(fn(Product $p) => ['product' => $p, 'spotlight' => 'novelty']));
    }

    public function product(int $id, ?string $slug = null)
    {
        $product = Product::with(['category', 'supplier', 'classificationValues.dimension'])->findOrFail($id);

        // Redirect to the canonical product URL when the slug does not match.
        $canonicalSlug = $product->clientPublicSlug();
        if ($slug !== $canonicalSlug) {
            return redirect()->route('clients.product', [
                'id' => $product->product_id,
                'slug' => $canonicalSlug,
            ], 301);
        }

        $relatedProducts = Product::with(['category'])
            ->where('category_id', $product->category_id)
            ->where('product_id', '!=', $product->product_id)
            ->limit(4)
            ->get();

        $cartCount = $this->getCartCount();

        $clientCanReview = false;
        $clientReview = null;
        if (Auth::guard('clients')->check()) {
            $clientId = (int) Auth::guard('clients')->id();
            $clientCanReview = SaleItem::query()
                ->where('product_id', $product->product_id)
                ->whereHas('sale', function ($q) use ($clientId) {
                    $q->where('client_id', $clientId)
                        ->where('status', 'completed');
                })
                ->exists();

            $clientReview = ProductReview::query()
                ->where('product_id', $product->product_id)
                ->where('client_id', $clientId)
                ->first();
        }

        $productReviews = ProductReview::query()
            ->with('client:user_id,name,first_surname')
            ->where('product_id', $product->product_id)
            ->whereNotNull('stars')
            ->latest()
            ->get();

        $averageStars = $productReviews->avg('stars');

        return view('client.product', compact(
            'product',
            'relatedProducts',
            'cartCount',
            'clientCanReview',
            'clientReview',
            'productReviews',
            'averageStars'
        ));
    }

    public function addToCart(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,product_id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($request->product_id);

        if (! $product->isPurchasableByClient()) {
            return response()->json(['success' => false, 'message' => Product::MSG_CLIENT_AGOTADO], 400);
        }

        if ($product->stock_current < $request->quantity) {
            return response()->json([
                'success' => false,
                'message' => $product->stock_current < 1
                    ? Product::MSG_CLIENT_AGOTADO
                    : Product::MSG_CLIENT_STOCK_INSUFICIENTE,
            ], 400);
        }

        $cart = Session::get('cart', []);
        $existingIndex = null;

        foreach ($cart as $index => $item) {
            if ($item['product_id'] == $request->product_id) {
                $existingIndex = $index;
                break;
            }
        }

        // Increase quantity when the product already exists in the cart.
        if ($existingIndex !== null) {
            $newQuantity = ($cart[$existingIndex]['quantity'] ?? 0) + $request->quantity;

            if ($newQuantity > $product->stock_current) {
                return response()->json([
                    'success' => false,
                    'message' => $product->stock_current < 1
                        ? Product::MSG_CLIENT_AGOTADO
                        : Product::MSG_CLIENT_STOCK_INSUFICIENTE,
                ], 400);
            }

            $cart[$existingIndex]['quantity'] = $newQuantity;
        } else {
            $mediaUrl = $product->getFirstMediaUrl('main_image');
            $cart[] = [
                'product_id' => $product->product_id,
                'name' => $product->name,
                'price' => $product->sale_price,
                'quantity' => $request->quantity,
                'image' => $mediaUrl,
            ];
        }

        Session::put('cart', $cart);

        return response()->json([
            'success' => true,
            'message' => 'Producto agregado al carrito',
            'cart_count' => $this->getCartCount(),
            'cart_total' => $this->getCartTotal(),
        ]);
    }

    private function getCartCount(): int
    {
        return count(Session::get('cart', []));
    }

    public function updateCart(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,product_id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($request->product_id);

        if (! $product->isPurchasableByClient()) {
            return response()->json(['success' => false, 'message' => Product::MSG_CLIENT_AGOTADO], 400);
        }

        if ($product->stock_current < $request->quantity) {
            return response()->json([
                'success' => false,
                'message' => $product->stock_current < 1
                    ? Product::MSG_CLIENT_AGOTADO
                    : Product::MSG_CLIENT_STOCK_INSUFICIENTE,
            ], 400);
        }

        $cart = Session::get('cart', []);

        foreach ($cart as $index => $item) {
            if ($item['product_id'] == $request->product_id) {
                $cart[$index]['quantity'] = $request->quantity;
                break;
            }
        }

        Session::put('cart', $cart);

        return response()->json([
            'success' => true,
            'message' => 'Carrito actualizado',
            'cart_count' => $this->getCartCount(),
            'cart_total' => $this->getCartTotal(),
        ]);
    }

    public function cart()
    {
        $cart = Session::get('cart', []);
        $cartItems = [];
        $total = 0;

        foreach ($cart as $item) {
            $product = Product::find($item['product_id']);

            // Rebuild cart rows using the latest product availability.
            if ($product && $product->isPurchasableByClient()) {
                $qty = min((int) $item['quantity'], $product->stock_current);
                if ($qty < 1) {
                    continue;
                }

                $subtotal = $item['price'] * $qty;
                $total += $subtotal;

                $mediaUrl = $product->getFirstMediaUrl('main_image');
                $cartItems[] = [
                    'product_id' => $product->product_id,
                    'name' => $product->name,
                    'price' => $item['price'],
                    'image_url' => $mediaUrl ?: asset('assets/images/products/' . ($product->image ?? 'default.png')),
                    'quantity' => $qty,
                    'stock_available' => $product->stock_current,
                    'subtotal' => $subtotal,
                ];
            }
        }

        Session::put('cart', $cartItems);

        $cartCount = $this->getCartCount();

        return view('client.cart', compact('cartItems', 'total', 'cartCount'));
    }

    public function removeFromCart(int $id)
    {
        $cart = Session::get('cart', []);

        if (empty($cart)) {
            return response()->json(['success' => false, 'message' => 'El carrito está vacío', 'cart_count' => 0, 'cart_total' => 0], 400);
        }

        $cart = array_values(array_filter($cart, fn($item) => $item['product_id'] != $id));

        Session::put('cart', $cart);

        return response()->json([
            'success' => true,
            'message' => 'Producto eliminado del carrito',
            'cart_count' => $this->getCartCount(),
            'cart_total' => $this->getCartTotal(),
        ]);
    }

    // Creates a pending web order and reserves stock immediately.
    public function checkout(Request $request, InventoryMovementService $inventoryService)
    {
        $cart = Session::get('cart', []);

        if (empty($cart)) {
            return response()->json(['success' => false, 'message' => 'El carrito está vacío'], 400);
        }

        DB::beginTransaction();
        try {
            $subtotal = 0;
            $validatedItems = [];

            foreach ($cart as $item) {
                $product = Product::find($item['product_id']);

                if (! $product || ! $product->isPurchasableByClient()) {
                    DB::rollBack();

                    return response()->json(['success' => false, 'message' => Product::MSG_CLIENT_AGOTADO], 400);
                }

                if ($product->stock_current < $item['quantity']) {
                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' => $product->stock_current < 1
                            ? Product::MSG_CLIENT_AGOTADO
                            : Product::MSG_CLIENT_STOCK_INSUFICIENTE,
                    ], 400);
                }

                $itemTotal = $item['price'] * $item['quantity'];
                $subtotal += $itemTotal;

                $validatedItems[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total' => $itemTotal,
                ];
            }

            /** @var Client|null $client */
            $client = Auth::guard('clients')->user();

            $sale = Sale::create([
                'invoice_number' => (new Sale)->generateInvoiceNumber(),
                'client_id' => $client?->user_id,
                'sale_date' => now(),
                'payment_method' => 'cash',
                'status' => 'pending',
                'order_source' => 'web_cart',
                'subtotal' => $subtotal,
                'iva' => 0,
                'discount' => 0,
                'total' => $subtotal,
                'notes' => 'Order placed from the online store',
            ]);

            foreach ($validatedItems as $item) {
                SaleItem::create([
                    'sale_id'       => $sale->sale_id,
                    'product_id'    => $item['product']->product_id,
                    'quantity'      => $item['quantity'],
                    'unit_price'    => $item['price'],
                    'unit_discount' => 0,
                    'total'         => $item['total'],
                ]);

                $inventoryService->recordWebCartSale(
                    product: $item['product'],
                    quantity: (int) $item['quantity'],
                    saleId: $sale->sale_id,
                );
            }

            Session::forget('cart');
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pedido creado exitosamente',
                'sale_id' => $sale->sale_id,
                'invoice_number' => $sale->invoice_number,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => 'Error al procesar el pedido: ' . $e->getMessage()], 500);
        }
    }

    public function invoices(Request $request)
    {
        /** @var Client $client */
        $client = Auth::guard('clients')->user();
        $tab = $request->query('tab', 'facturas');
        $pendingReviewProducts = collect();

        $activeStatuses = $this->activeClientInvoiceStatuses();
        $cancelledStatuses = $this->cancelledClientInvoiceStatuses();

        if ($tab === 'historial') {
            $orders = Sale::with(['saleItems.product'])
                ->where('client_id', $client->user_id)
                ->where('status', 'completed')
                ->orderByDesc('sale_date')
                ->get();

            $pendingReviewProducts = ProductReview::query()
                ->with('product:product_id,name')
                ->where('client_id', $client->user_id)
                ->whereNull('stars')
                ->whereHas('product')
                ->get()
                ->map(function (ProductReview $review) {
                    return [
                        'product_id' => (int) $review->product_id,
                        'name' => (string) ($review->product->name ?? 'Producto'),
                    ];
                })
                ->values();
        } elseif ($tab === 'canceladas') {
            $orders = Sale::with(['saleItems.product'])
                ->where('client_id', $client->user_id)
                ->whereIn('status', $cancelledStatuses)
                ->orderByDesc('sale_date')
                ->get();
        } else {
            $tab = 'facturas';

            $orders = Sale::with(['saleItems.product'])
                ->where('client_id', $client->user_id)
                ->whereIn('status', $activeStatuses)
                ->orderByDesc('sale_date')
                ->get();
        }

        $cartCount = $this->getCartCount();

        $invoiceCount = Sale::where('client_id', $client->user_id)
            ->whereIn('status', $activeStatuses)
            ->count();

        return view('client.Invoices', compact(
            'orders',
            'cartCount',
            'invoiceCount',
            'tab',
            'pendingReviewProducts'
        ));
    }

    public function invoicesHeartbeat()
    {
        /** @var Client $client */
        $client = Auth::guard('clients')->user();

        $count = Sale::where('client_id', $client->user_id)
            ->whereIn('status', $this->activeClientInvoiceStatuses())
            ->count();

        return response()->json(['count' => $count]);
    }

    public function notifications()
    {
        /** @var Client $client */
        $client = Auth::guard('clients')->user();
        $cartCount = $this->getCartCount();

        $notifications = $client->notifications()
            ->latest()
            ->paginate(20);

        return view('client.notifications', compact('notifications', 'cartCount'));
    }

    public function showInvoice(Sale $sale)
    {
        $client = Auth::guard('clients')->user();

        if ((int) $sale->client_id !== (int) $client->user_id) {
            abort(404);
        }

        $sale->load(['saleItems.product']);

        $cartCount = $this->getCartCount();

        $invoiceCount = Sale::where('client_id', $client->user_id)
            ->whereIn('status', $this->activeClientInvoiceStatuses())
            ->count();

        return view('client.invoice-detail', compact('sale', 'cartCount', 'invoiceCount'));
    }

    private function getCartTotal(): float
    {
        return array_reduce(
            Session::get('cart', []),
            fn($carry, $item) => $carry + $item['price'] * $item['quantity'],
            0
        );
    }

    private function activeClientInvoiceStatuses(): array
    {
        return ['pending', 'ready_to_pickup'];
    }
    private function cancelledClientInvoiceStatuses(): array
    {
        return ['cancelled', 'canceled'];
    }
}
