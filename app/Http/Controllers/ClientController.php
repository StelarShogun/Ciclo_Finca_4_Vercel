<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ClientController extends Controller
{
    public function home()
    {
        // Featured products: active, in stock, latest first
        $featuredProducts = Product::with(['category'])
            ->where('status', 'active')
            ->where('stock_current', '>', 0)
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        $categories = Category::whereNull('parent_category_id')
            ->orderBy('name')
            ->get();

        $cartCount = $this->getCartCount();

        return view('clients.home', compact('featuredProducts', 'categories', 'cartCount'));
    }

    public function catalog(Request $request)
    {
        $query = Product::with(['category'])
            ->where('status', 'active')
            ->where('stock_current', '>', 0);

        // Search by name or description
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $searchTerm . '%');
            });
        }

        // Filter by category (parent = parent + all its children; child = that category only)
        $selectedCategory = null;
        $subcategories = collect();
        $parentCategoryForSubcats = null; // categoría padre para mostrar "En X: Todas, sub1, sub2"
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
                    $parentCategoryForSubcats = $selectedCategory->parentCategory;
                    if ($parentCategoryForSubcats) {
                        $subcategories = Category::where('parent_category_id', $parentCategoryForSubcats->category_id)->orderBy('name')->get();
                    }
                }
            }
        }

        // Price range filter: validate min <= max; otherwise redirect with error
        $minPrice = $request->filled('min_price') ? $request->input('min_price') : null;
        $maxPrice = $request->filled('max_price') ? $request->input('max_price') : null;
        if (is_numeric($minPrice) && is_numeric($maxPrice) && (float) $minPrice > (float) $maxPrice) {
            return redirect()->route('clients.catalog', $request->except('min_price', 'max_price', 'page'))
                ->withInput()
                ->withErrors(['price_range' => 'El precio mínimo debe ser menor o igual al precio máximo.']);
        }
        if (is_numeric($minPrice) && is_numeric($maxPrice) && (float) $minPrice <= (float) $maxPrice) {
            $query->where('sale_price', '>=', $minPrice)->where('sale_price', '<=', $maxPrice);
        } elseif (is_numeric($minPrice)) {
            $query->where('sale_price', '>=', $minPrice);
        } elseif (is_numeric($maxPrice)) {
            $query->where('sale_price', '<=', $maxPrice);
        }

        $sort  = $request->get('sort', 'created_at');
        $order = $request->get('direction', 'desc');

        if ($sort === 'price') {
            $query->orderBy('sale_price', $order);
        } elseif ($sort === 'name') {
            $query->orderBy('name', $order);
        } else {
            $query->orderBy('created_at', $order);
        }

        $perPage  = $request->get('per_page', 12);
        $products = $query->paginate($perPage)->withQueryString();

        $categories = Category::whereNull('parent_category_id')
            ->orderBy('name')
            ->get();

        $cartCount = $this->getCartCount();

        return view('clients.catalog', compact('products', 'categories', 'cartCount', 'selectedCategory', 'subcategories', 'parentCategoryForSubcats'));
    }

    public function product($id)
    {
        $product = Product::with(['category', 'supplier'])
            ->where('status', 'active')
            ->findOrFail($id);

        // Related products from the same category
        $relatedProducts = Product::with(['category'])
            ->where('category_id', $product->category_id)
            ->where('product_id', '!=', $product->product_id)
            ->where('status', 'active')
            ->where('stock_current', '>', 0)
            ->limit(4)
            ->get();

        $cartCount = $this->getCartCount();

        return view('clients.product', compact('product', 'relatedProducts', 'cartCount'));
    }

    public function addToCart(Request $request)
    {
        $request->merge([
            'product_id' => $request->input('product_id'),
            'quantity'   => $request->input('quantity'),
        ]);

        $request->validate([
            'product_id' => 'required|exists:products,product_id',
            'quantity'   => 'required|integer|min:1'
        ]);

        $product = Product::findOrFail($request->product_id);

        // Check product availability and stock
        if ($product->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Este producto no está disponible'
            ], 400);
        }

        if ($product->stock_current < $request->quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Stock insuficiente. Disponible: ' . $product->stock_current
            ], 400);
        }

        $cart = Session::get('cart', []);

        // Check if product already exists in cart
        $existingIndex = null;
        foreach ($cart as $index => $item) {
            if ($item['product_id'] == $request->product_id) {
                $existingIndex = $index;
                break;
            }
        }

        if ($existingIndex !== null) {
            // Increment quantity, re-validate stock
            $newQuantity = ($cart[$existingIndex]['quantity'] ?? 0) + $request->quantity;

            if ($newQuantity > $product->stock_current) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock insuficiente. Disponible: ' . $product->stock_current
                ], 400);
            }

            $cart[$existingIndex]['quantity'] = $newQuantity;
        } else {
            $cart[] = [
                'product_id'      => $product->product_id,
                'name'            => $product->name,
                'price'           => $product->sale_price,
                'image'           => $product->image ?? 'default.png',
                'quantity'        => $request->quantity,
                'stock_available' => $product->stock_current
            ];
        }

        Session::put('cart', $cart);

        return response()->json([
            'success'    => true,
            'message'    => 'Producto agregado al carrito',
            'cart_count' => $this->getCartCount(),
            'cart_total' => $this->getCartTotal()
        ]);
    }

    public function cart()
    {
        $cart      = Session::get('cart', []);
        $cartItems = [];
        $total     = 0;

        foreach ($cart as $item) {
            $product = Product::find($item['product_id']);
            if ($product && $product->status === 'active') {
                $subtotal = $item['price'] * $item['quantity'];
                $total   += $subtotal;

                $cartItems[] = [
                    'product_id'      => $product->product_id,
                    'name'            => $product->name,
                    'price'           => $item['price'],
                    'image'           => $product->image ?? 'default.png',
                    'quantity'        => $item['quantity'],
                    'stock_available' => $product->stock_current,
                    'subtotal'        => $subtotal
                ];
            }
        }

        // Overwrite session to drop any inactive products
        Session::put('cart', $cartItems);

        $cartCount = $this->getCartCount();

        return view('clients.cart', compact('cartItems', 'total', 'cartCount'));
    }

    public function updateCart(Request $request)
    {
        $request->merge([
            'product_id' => $request->input('product_id'),
            'quantity'   => $request->input('quantity'),
        ]);

        $request->validate([
            'product_id' => 'required|exists:products,product_id',
            'quantity'   => 'required|integer|min:1'
        ]);

        $product = Product::findOrFail($request->product_id);

        if ($product->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Este producto no está disponible'
            ], 400);
        }

        if ($product->stock_current < $request->quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Stock insuficiente. Disponible: ' . $product->stock_current
            ], 400);
        }

        $cart = Session::get('cart', []);

        // Update quantity for the matching product
        foreach ($cart as $index => $item) {
            if ($item['product_id'] == $request->product_id) {
                $cart[$index]['quantity'] = $request->quantity;
                break;
            }
        }

        Session::put('cart', $cart);

        return response()->json([
            'success'    => true,
            'message'    => 'Carrito actualizado',
            'cart_count' => $this->getCartCount(),
            'cart_total' => $this->getCartTotal()
        ]);
    }

    public function removeFromCart($id)
    {
        $cart = Session::get('cart', []);

        // Remove item by product ID and re-index the array
        $cart = array_filter($cart, fn($item) => $item['product_id'] != $id);

        Session::put('cart', array_values($cart));

        return response()->json([
            'success'    => true,
            'message'    => 'Producto eliminado del carrito',
            'cart_count' => $this->getCartCount(),
            'cart_total' => $this->getCartTotal()
        ]);
    }

    public function checkout(Request $request)
    {
        $cart = Session::get('cart', []);

        if (empty($cart)) {
            return response()->json([
                'success' => false,
                'message' => 'El carrito está vacío'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $subtotal       = 0;
            $validatedItems = [];

            // Validate stock and compute totals before writing anything
            foreach ($cart as $item) {
                $product = Product::find($item['product_id']);

                if (!$product || $product->status !== 'active') {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'El producto "' . ($item['name'] ?? '') . '" ya no está disponible'
                    ], 400);
                }

                if ($product->stock_current < $item['quantity']) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Stock insuficiente para "' . $product->name . '". Disponible: ' . $product->stock_current
                    ], 400);
                }

                $itemTotal = $item['price'] * $item['quantity'];
                $subtotal += $itemTotal;

                $validatedItems[] = [
                    'product'  => $product,
                    'quantity' => $item['quantity'],
                    'price'    => $item['price'],
                    'total'    => $itemTotal
                ];
            }

            // Create the sale record
            $sale = Sale::create([
                'invoice_number' => (new Sale())->generateInvoiceNumber(),
                'customer_id'    => Auth::id(),
                'seller_id'      => Auth::id(),
                'sale_date'      => now(),
                'payment_method' => 'cash',
                'status'         => 'pending',
                'subtotal'       => $subtotal,
                'iva'            => 0,
                'discount'       => 0,
                'total'          => $subtotal,
                'notes'          => 'Order placed from the online store'
            ]);

            // Create sale items and decrement stock
            foreach ($validatedItems as $item) {
                SaleItem::create([
                    'sale_id'       => $sale->sale_id,
                    'product_id'    => $item['product']->product_id,
                    'quantity'      => $item['quantity'],
                    'unit_price'    => $item['price'],
                    'unit_discount' => 0,
                    'total'         => $item['total']
                ]);

                $item['product']->decrement('stock_current', $item['quantity']);
            }

            Session::forget('cart');
            DB::commit();

            return response()->json([
                'success'        => true,
                'message'        => 'Pedido creado exitosamente',
                'sale_id'        => $sale->sale_id,
                'invoice_number' => $sale->invoice_number
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getCartCount()
    {
        return count(Session::get('cart', []));
    }

    private function getCartTotal()
    {
        $total = 0;
        foreach (Session::get('cart', []) as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        return $total;
    }

    public function clearCart()
    {
        Session::forget('cart');

        return response()->json([
            'success'    => true,
            'message'    => 'Carrito vaciado exitosamente',
            'cart_count' => 0,
            'cart_total' => 0
        ]);
    }
}