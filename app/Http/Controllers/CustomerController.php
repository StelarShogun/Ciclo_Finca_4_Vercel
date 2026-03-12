<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class CustomerController extends Controller
{
    /**
     * Home page with featured products and categories
     */
    public function home()
    {
        $productosDestacados = Product::with(['category'])
            ->where('status', 'active')
            ->where('stock_current', '>', 0)
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        $categorias = Category::whereNull('parent_category_id')
            ->orderBy('name')
            ->get();

        $cartCount = $this->getCartCount();

        return view('customers.home', compact('productosDestacados', 'categorias', 'cartCount'));
    }

    /**
     * Product catalog with filters and search
     */
    public function catalog(Request $request)
    {
        $query = Product::with(['category'])
            ->where('status', 'active')
            ->where('stock_current', '>', 0);

        if ($request->filled('buscar')) {
            $searchTerm = $request->buscar;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $searchTerm . '%');
            });
        }

        if ($request->filled('categoria_id')) {
            $query->where('category_id', $request->categoria_id);
        }

        // Validate price range before applying
        if ($request->filled('precio_min') && $request->filled('precio_max')) {
            if ((float) $request->precio_min > (float) $request->precio_max) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'error' => 'El precio mínimo no puede ser mayor que el precio máximo.',
                        'html'  => null,
                    ], 422);
                }
                return redirect()->route('customers.catalog', $request->except(['precio_min', 'precio_max']))
                    ->withErrors(['precio' => 'El precio mínimo no puede ser mayor que el precio máximo.']);
            }
        }

        if ($request->filled('precio_min')) {
            $query->where('sale_price', '>=', $request->precio_min);
        }
        if ($request->filled('precio_max')) {
            $query->where('sale_price', '<=', $request->precio_max);
        }

        $sort = $request->get('ordenar', 'fecha_creacion');
        $order = $request->get('direccion', 'desc');

        if ($sort === 'precio') {
            $query->orderBy('sale_price', $order);
        } elseif ($sort === 'nombre') {
            $query->orderBy('name', $order);
        } else {
            $query->orderBy('created_at', $order);
        }

        $perPage = $request->get('por_pagina', 12);
        $productos = $query->paginate($perPage)->withQueryString();

        $categorias = Category::whereNull('parent_category_id')
            ->orderBy('name')
            ->get();

        $cartCount = $this->getCartCount();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'html' => view('customers.partials.catalog-results', compact('productos'))->render(),
            ]);
        }

        return view('customers.catalog', compact('productos', 'categorias', 'cartCount'));
    }

    /**
     * Single product detail
     */
    public function product($id)
    {
        $producto = Product::with(['category', 'supplier'])
            ->where('status', 'active')
            ->findOrFail($id);

        $productosRelacionados = Product::with(['category'])
            ->where('category_id', $producto->category_id)
            ->where('product_id', '!=', $producto->product_id)
            ->where('status', 'active')
            ->where('stock_current', '>', 0)
            ->limit(4)
            ->get();

        $cartCount = $this->getCartCount();

        return view('customers.product', compact('producto', 'productosRelacionados', 'cartCount'));
    }

    /**
     * Add product to cart
     */
    public function addToCart(Request $request)
    {
        $productId = $request->input('product_id') ?? $request->input('producto_id');
        $quantity = $request->input('quantity') ?? $request->input('cantidad');

        $request->merge([
            'product_id' => $productId,
            'quantity' => $quantity,
        ]);

        $request->validate([
            'product_id' => 'required|exists:products,product_id',
            'quantity' => 'required|integer|min:1',
        ]);

        $producto = Product::findOrFail($request->product_id);

        if ($producto->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Este producto no está disponible',
            ], 400);
        }

        if ($producto->stock_current < $request->quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Stock insuficiente. Disponible: ' . $producto->stock_current,
            ], 400);
        }

        $cart = Session::get('carrito', []);

        $productoIndex = null;
        foreach ($cart as $index => $item) {
            if (($item['product_id'] ?? $item['producto_id'] ?? null) == $request->product_id) {
                $productoIndex = $index;
                break;
            }
        }

        if ($productoIndex !== null) {
            $nuevaCantidad = ($cart[$productoIndex]['quantity'] ?? $cart[$productoIndex]['cantidad'] ?? 0) + $request->quantity;

            if ($nuevaCantidad > $producto->stock_current) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock insuficiente. Disponible: ' . $producto->stock_current,
                ], 400);
            }

            $cart[$productoIndex]['quantity'] = $nuevaCantidad;
        } else {
            $cart[] = [
                'product_id' => $producto->product_id,
                'name' => $producto->name,
                'price' => $producto->sale_price,
                'image' => $producto->image ?? 'default.png',
                'quantity' => $request->quantity,
                'stock_available' => $producto->stock_current,
            ];
        }

        Session::put('carrito', $cart);

        return response()->json([
            'success' => true,
            'message' => 'Producto agregado al carrito',
            'cart_count' => $this->getCartCount(),
            'cart_total' => $this->getCartTotal(),
        ]);
    }

    /**
     * Cart page
     */
    public function cart()
    {
        $cart = Session::get('carrito', []);
        $cartItems = [];
        $total = 0;

        foreach ($cart as $item) {
            $producto = Product::find($item['product_id']);
            if ($producto && $producto->status === 'active') {
                $subtotal = $item['price'] * $item['quantity'];
                $total += $subtotal;

                $cartItems[] = [
                    'producto_id' => $producto->product_id,
                    'nombre' => $producto->name,
                    'precio' => $item['price'],
                    'imagen' => $producto->image ?? 'default.png',
                    'cantidad' => $item['quantity'],
                    'stock_disponible' => $producto->stock_current,
                    'subtotal' => $subtotal,
                ];
            }
        }

        $cartCount = $this->getCartCount();

        return view('customers.cart', compact('cartItems', 'total', 'cartCount'));
    }

    /**
     * Update cart item quantity
     */
    public function updateCart(Request $request)
    {
        $productId = $request->input('product_id') ?? $request->input('producto_id');
        $quantity = $request->input('quantity') ?? $request->input('cantidad');

        $request->merge([
            'product_id' => $productId,
            'quantity' => $quantity,
        ]);

        $request->validate([
            'product_id' => 'required|exists:products,product_id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($request->product_id);

        if ($product->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Este producto no está disponible',
            ], 400);
        }

        if ($product->stock_current < $request->quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Stock insuficiente. Disponible: ' . $product->stock_current,
            ], 400);
        }

        $cart = Session::get('carrito', []);

        foreach ($cart as $index => $item) {
            if ($item['product_id'] == $request->product_id) {
                $cart[$index]['quantity'] = $request->quantity;
                break;
            }
        }

        Session::put('carrito', $cart);

        return response()->json([
            'success' => true,
            'message' => 'Carrito actualizado',
            'cart_count' => $this->getCartCount(),
            'cart_total' => $this->getCartTotal(),
        ]);
    }

    /**
     * Remove item from cart
     */
    public function removeFromCart($id)
    {
        $cart = Session::get('carrito', []);
        $cart = array_filter($cart, function ($item) use ($id) {
            return $item['product_id'] != $id;
        });

        Session::put('carrito', array_values($cart));

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Producto eliminado del carrito',
                'cart_count' => $this->getCartCount(),
                'cart_total' => $this->getCartTotal(),
            ]);
        }

        return redirect()->route('customers.cart')->with('status', 'Producto eliminado del carrito');
    }

    private function getCartCount()
    {
        $cart = Session::get('carrito', []);

        return count($cart);
    }

    private function getCartTotal()
    {
        $cart = Session::get('carrito', []);
        $total = 0;

        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        return $total;
    }
}
