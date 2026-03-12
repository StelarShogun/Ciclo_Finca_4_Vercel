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

class ClienteController extends Controller
{
    /**
     * Muestra la página home con productos destacados y categorías
     */
    public function home()
    {
        // Obtener productos destacados (activos, con stock, limitados)
        $productosDestacados = Product::with(['category'])
            ->where('status', 'active')
            ->where('stock_current', '>', 0)
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        // Obtener categorías principales
        $categorias = Category::whereNull('parent_category_id')
            ->orderBy('name')
            ->get();

        // Contar items en carrito
        $cartCount = $this->getCartCount();

        return view('clientes.home', compact('productosDestacados', 'categorias', 'cartCount'));
    }

    /**
     * Muestra el catálogo de productos con filtros y búsqueda
     */
    public function catalogo(Request $request)
    {
        $query = Product::with(['category'])
            ->where('status', 'active')
            ->where('stock_current', '>', 0);

        // Búsqueda por nombre o descripción
        if ($request->filled('buscar')) {
            $searchTerm = $request->buscar;
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $searchTerm . '%');
            });
        }

        // Filtro por categoría
        if ($request->filled('categoria_id')) {
            $query->where('category_id', $request->categoria_id);
        }

        // Filtro por rango de precio
        if ($request->filled('precio_min')) {
            $query->where('sale_price', '>=', $request->precio_min);
        }
        if ($request->filled('precio_max')) {
            $query->where('sale_price', '<=', $request->precio_max);
        }

        // Ordenamiento
        $sort = $request->get('ordenar', 'fecha_creacion');
        $order = $request->get('direccion', 'desc');
        
        if ($sort === 'precio') {
            $query->orderBy('sale_price', $order);
        } elseif ($sort === 'nombre') {
            $query->orderBy('name', $order);
        } else {
            $query->orderBy('created_at', $order);
        }

        // Paginación
        $perPage = $request->get('por_pagina', 12);
        $productos = $query->paginate($perPage)->withQueryString();

        // Obtener categorías para el filtro
        $categorias = Category::whereNull('parent_category_id')
            ->orderBy('name')
            ->get();

        // Contar items en carrito
        $cartCount = $this->getCartCount();

        return view('clientes.catalogo', compact('productos', 'categorias', 'cartCount'));
    }

    /**
     * Vista detallada de un producto
     */
    public function producto($id)
    {
        $producto = Product::with(['category', 'supplier'])
            ->where('status', 'active')
            ->findOrFail($id);

        // Productos relacionados (misma categoría)
        $productosRelacionados = Product::with(['category'])
            ->where('category_id', $producto->category_id)
            ->where('product_id', '!=', $producto->product_id)
            ->where('status', 'active')
            ->where('stock_current', '>', 0)
            ->limit(4)
            ->get();

        $cartCount = $this->getCartCount();

        return view('clientes.producto', compact('producto', 'productosRelacionados', 'cartCount'));
    }

    /**
     * Agrega un producto al carrito
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
            'quantity' => 'required|integer|min:1'
        ]);

        $producto = Product::findOrFail($request->product_id);

        // Validar que el producto esté activo y tenga stock
        if ($producto->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Este producto no está disponible'
            ], 400);
        }

        if ($producto->stock_current < $request->quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Stock insuficiente. Disponible: ' . $producto->stock_current
            ], 400);
        }

        // Obtener carrito actual
        $cart = Session::get('carrito', []);

        // Verificar si el producto ya está en el carrito
        $productoIndex = null;
        foreach ($cart as $index => $item) {
            if (($item['product_id'] ?? $item['producto_id'] ?? null) == $request->product_id) {
                $productoIndex = $index;
                break;
            }
        }

        if ($productoIndex !== null) {
            // Actualizar cantidad
            $nuevaCantidad = ($cart[$productoIndex]['quantity'] ?? $cart[$productoIndex]['cantidad'] ?? 0) + $request->quantity;
            
            if ($nuevaCantidad > $producto->stock_current) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock insuficiente. Disponible: ' . $producto->stock_current
                ], 400);
            }

            $cart[$productoIndex]['quantity'] = $nuevaCantidad;
        } else {
            // Agregar nuevo producto
            $cart[] = [
                'product_id' => $producto->product_id,
                'name' => $producto->name,
                'price' => $producto->sale_price,
                'image' => $producto->image ?? 'default.png',
                'quantity' => $request->quantity,
                'stock_available' => $producto->stock_current
            ];
        }

        Session::put('carrito', $cart);

        return response()->json([
            'success' => true,
            'message' => 'Producto agregado al carrito',
            'cart_count' => $this->getCartCount(),
            'cart_total' => $this->getCartTotal()
        ]);
    }

    /**
     * Muestra el carrito
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
                    'subtotal' => $subtotal
                ];
            }
        }

        // Actualizar carrito en sesión (eliminar productos que ya no están activos)
        Session::put('carrito', array_map(function($item) {
            return [
                'product_id' => $item['producto_id'],
                'name' => $item['nombre'],
                'price' => $item['precio'],
                'image' => $item['imagen'],
                'quantity' => $item['cantidad'],
                'stock_available' => $item['stock_disponible']
            ];
        }, $cartItems));

        $cartCount = $this->getCartCount();

        return view('clientes.carrito', compact('cartItems', 'total', 'cartCount'));
    }

    /**
     * Actualiza la cantidad de un producto en el carrito
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
            'quantity' => 'required|integer|min:1'
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
            'cart_total' => $this->getCartTotal()
        ]);
    }

    /**
     * Elimina un producto del carrito
     */
    public function removeFromCart($id)
    {
        $cart = Session::get('carrito', []);
        $cart = array_filter($cart, function($item) use ($id) {
            return $item['product_id'] != $id;
        });

        Session::put('carrito', array_values($cart));

        return response()->json([
            'success' => true,
            'message' => 'Producto eliminado del carrito',
            'cart_count' => $this->getCartCount(),
            'cart_total' => $this->getCartTotal()
        ]);
    }

    /**
     * Procesa el checkout: crea la venta, vacía el carrito
     */
    public function checkout(Request $request)
    {
        $cart = Session::get('carrito', []);

        if (empty($cart)) {
            return response()->json([
                'success' => false,
                'message' => 'El carrito está vacío'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $subtotal = 0;
            $validatedItems = [];

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
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total' => $itemTotal
                ];
            }

            $sale = Sale::create([
                'invoice_number' => (new Sale())->generateInvoiceNumber(),
                'customer_id' => Auth::id(),
                'seller_id' => Auth::id(),
                'sale_date' => now(),
                'payment_method' => 'cash',
                'status' => 'pending',
                'subtotal' => $subtotal,
                'iva' => 0,
                'discount' => 0,
                'total' => $subtotal,
                'notes' => 'Pedido realizado desde la tienda en línea'
            ]);

            foreach ($validatedItems as $item) {
                SaleItem::create([
                    'sale_id' => $sale->sale_id,
                    'product_id' => $item['product']->product_id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'unit_discount' => 0,
                    'total' => $item['total']
                ]);

                $item['product']->decrement('stock_current', $item['quantity']);
            }

            Session::forget('carrito');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pedido creado exitosamente',
                'sale_id' => $sale->sale_id,
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

    /**
     * Obtiene el conteo de items en el carrito
     */
    private function getCartCount()
    {
        $cart = Session::get('carrito', []);
        return count($cart);
    }

    /**
     * Obtiene el total del carrito
     */
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
