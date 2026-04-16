<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class ClientPageController extends Controller
{
    public function home()
    {
        // Featured products: active, in stock, latest first
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
        // Misma base que inventario admin: todos los productos; disponibilidad solo en la vista (CF4-62).
        $query = Product::with(['category']);

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%'.$searchTerm.'%')
                    ->orWhere('description', 'like', '%'.$searchTerm.'%');
            });
        }

        // Parent category includes itself and all children; child category filters only itself
        $selectedCategory = null;
        $subcategories = collect();
        $parentCategoryForSubcats = null;

        if ($request->filled('category_id')) {
            $selectedCategory = Category::find($request->category_id);
            if ($selectedCategory) {
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

        // Reject invalid range before applying filters
        if (is_numeric($minPrice) && is_numeric($maxPrice) && (float) $minPrice > (float) $maxPrice) {
            return redirect()->route('clients.catalog', $request->except('min_price', 'max_price', 'page'))
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
            ->orderBy('name')
            ->get();

        $cartCount = $this->getCartCount();
        $catalogSpotlight = $this->catalogSpotlightProductRows();

        return view('client.catalog', compact(
            'products', 'categories', 'cartCount',
            'selectedCategory', 'subcategories', 'parentCategoryForSubcats',
            'catalogSpotlight'
        ));
    }

    /**
     * Featured and newest active products for the catalog spotlight (CF4-29).
     *
     * @return Collection<int, array{product: Product, spotlight: 'featured'|'novelty'}>
     */
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

        return $featured->map(fn (Product $p) => ['product' => $p, 'spotlight' => 'featured'])
            ->concat($novelties->map(fn (Product $p) => ['product' => $p, 'spotlight' => 'novelty']));
    }

    public function product($id, ?string $slug = null)
    {
        $product = Product::with(['category', 'supplier', 'classificationValues.dimension'])->findOrFail($id);

        $canonicalSlug = $product->clientPublicSlug();
        if ($slug !== $canonicalSlug) {
            return redirect()->route('clients.product', [
                'id' => $product->product_id,
                'slug' => $canonicalSlug,
            ], 301);
        }

        // Relacionados: misma regla de visibilidad que el catálogo (pueden estar agotados)
        $relatedProducts = Product::with(['category'])
            ->where('category_id', $product->category_id)
            ->where('product_id', '!=', $product->product_id)
            ->limit(4)
            ->get();

        $cartCount = $this->getCartCount();

        return view('client.product', compact('product', 'relatedProducts', 'cartCount'));
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

        if ($existingIndex !== null) {
            // Accumulate quantity and re-validate against current stock
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
            $cart[] = [
                'product_id' => $product->product_id,
                'name' => $product->name,
                'price' => $product->sale_price,
                'image' => $product->image ?? 'default.png',
                'quantity' => $request->quantity,
                'stock_available' => $product->stock_current,
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

    public function cart()
    {
        $cart = Session::get('cart', []);
        $cartItems = [];
        $total = 0;

        foreach ($cart as $item) {
            $product = Product::find($item['product_id']);

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

    public function removeFromCart($id)
    {
        $cart = Session::get('cart', []);

        if (empty($cart)) {
            return response()->json(['success' => false, 'message' => 'El carrito está vacío', 'cart_count' => 0, 'cart_total' => 0], 400);
        }

        // Filter out the item and re-index
        $cart = array_values(array_filter($cart, fn ($item) => $item['product_id'] != $id));

        Session::put('cart', $cart);

        return response()->json([
            'success' => true,
            'message' => 'Producto eliminado del carrito',
            'cart_count' => $this->getCartCount(),
            'cart_total' => $this->getCartTotal(),
        ]);
    }

    public function checkout(Request $request)
    {
        $cart = Session::get('cart', []);

        if (empty($cart)) {
            return response()->json(['success' => false, 'message' => 'El carrito está vacío'], 400);
        }

        DB::beginTransaction();
        try {
            $subtotal = 0;
            $validatedItems = [];

            // Validate availability and compute totals before any writes
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

            $client = Auth::guard('clients')->user();

            // Web cart orders are linked via client_id (see migration CF4)
            $sale = Sale::create([
                'invoice_number' => (new Sale)->generateInvoiceNumber(),
                'client_id' => $client?->user_id,
                'customer_id' => null,
                'seller_id' => null,
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

            // Persist line items and deduct stock atomically
            foreach ($validatedItems as $item) {
                SaleItem::create([
                    'sale_id' => $sale->sale_id,
                    'product_id' => $item['product']->product_id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'unit_discount' => 0,
                    'total' => $item['total'],
                ]);

                $item['product']->decrement('stock_current', $item['quantity']);
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

            return response()->json(['success' => false, 'message' => 'Error al procesar el pedido: '.$e->getMessage()], 500);
        }
    }

    private function getCartCount(): int
    {
        return count(Session::get('cart', []));
    }

    private function getCartTotal(): float
    {
        return array_reduce(
            Session::get('cart', []),
            fn ($carry, $item) => $carry + $item['price'] * $item['quantity'],
            0
        );
    }

    public function clearCart()
    {
        Session::forget('cart');

        return response()->json([
            'success' => true,
            'message' => 'Carrito vaciado exitosamente',
            'cart_count' => 0,
            'cart_total' => 0,
        ]);
    }

    public function invoices()
    {
        $client = Auth::guard('clients')->user();

        $orders = Sale::with(['saleItems.product'])
            ->where('client_id', $client->user_id)
            ->where('status', 'pending')
            ->orderByDesc('sale_date')
            ->paginate(15);

        $cartCount = $this->getCartCount();

        return view('client.Invoices', compact('orders', 'cartCount'));
    }
}
