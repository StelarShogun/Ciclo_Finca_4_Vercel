<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\Category;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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

        // Resolver cat/sub (slugs) y filtrar por categoría
        $categoriaPadreActual = null;
        $subcategoriaActual = null;
        $categorias = Category::with('childCategories')->whereNull('parent_category_id')->orderBy('name')->get();

        if ($request->filled('cat')) {
            $catSlug = $request->get('cat');
            $categoriaPadreActual = $categorias->first(fn($c) => Str::slug($c->name) === $catSlug);

            if ($categoriaPadreActual) {
                if ($request->filled('sub')) {
                    $subSlug = $request->get('sub');
                    $subcategoriaActual = $categoriaPadreActual->childCategories->first(fn($c) => Str::slug($c->name) === $subSlug);
                    if ($subcategoriaActual) {
                        $query->where('category_id', $subcategoriaActual->category_id);
                    } else {
                        $categoryIds = $categoriaPadreActual->childCategories->pluck('category_id')->push($categoriaPadreActual->category_id)->toArray();
                        $query->whereIn('category_id', $categoryIds);
                    }
                } else {
                    $categoryIds = $categoriaPadreActual->childCategories->pluck('category_id')->push($categoriaPadreActual->category_id)->toArray();
                    $query->whereIn('category_id', $categoryIds);
                }
            }
        }

        // Filtro por rango de precio (validar que mínimo no sea mayor que máximo)
        $errorRangoPrecio = false;
        $tieneFiltroPrecio = $request->filled('precio_min') || $request->filled('precio_max');
        if ($request->filled('precio_min') && $request->filled('precio_max')) {
            $min = (float) $request->precio_min;
            $max = (float) $request->precio_max;
            if ($min > $max) {
                $errorRangoPrecio = true;
                // No aplicar filtro de precio para mostrar resultados con el resto de filtros
            } else {
                $query->where('sale_price', '>=', $min)->where('sale_price', '<=', $max);
            }
        } elseif ($request->filled('precio_min')) {
            $query->where('sale_price', '>=', (float) $request->precio_min);
        } elseif ($request->filled('precio_max')) {
            $query->where('sale_price', '<=', (float) $request->precio_max);
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

        // Contar items en carrito
        $cartCount = $this->getCartCount();

        return view('clientes.catalogo', compact(
            'productos',
            'categorias',
            'cartCount',
            'errorRangoPrecio',
            'tieneFiltroPrecio',
            'categoriaPadreActual',
            'subcategoriaActual'
        ));
    }

    /**
     * Vista detallada de un producto (CF4-27).
     * Muestra imagen, nombre, precio, descripción y disponibilidad/stock.
     * Si ocurre un error al cargar, muestra mensaje informativo.
     */
    public function producto($id)
    {
        try {
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
        } catch (ModelNotFoundException $e) {
            $cartCount = $this->getCartCount();
            return response()->view('clientes.producto-error', compact('cartCount'), 404);
        } catch (\Exception $e) {
            $cartCount = $this->getCartCount();
            return response()->view('clientes.producto-error', compact('cartCount'), 500);
        }
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

        $clientId = Auth::guard('clients')->id();
        $item = CartItem::where('client_id', $clientId)->where('product_id', $request->product_id)->first();

        if ($item) {
            $nuevaCantidad = $item->quantity + $request->quantity;
            if ($nuevaCantidad > $producto->stock_current) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock insuficiente. Disponible: ' . $producto->stock_current
                ], 400);
            }
            $item->update(['quantity' => $nuevaCantidad]);
        } else {
            CartItem::create([
                'client_id' => $clientId,
                'product_id' => $producto->product_id,
                'quantity' => $request->quantity,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Producto agregado al carrito',
            'cart_count' => $this->getCartCount(),
            'cart_total' => $this->getCartTotal()
        ]);
    }

    /**
     * Muestra el carrito (solo Client logueado; middleware auth:clients)
     */
    public function cart()
    {
        $clientId = Auth::guard('clients')->id();
        $items = CartItem::where('client_id', $clientId)->with('product')->get();

        $cartItems = [];
        $total = 0;

        foreach ($items as $item) {
            $producto = $item->product;
            if ($producto && $producto->status === 'active') {
                $price = $producto->sale_price;
                $subtotal = $price * $item->quantity;
                $total += $subtotal;

                $cartItems[] = [
                    'product_id' => $producto->product_id,
                    'name' => $producto->name,
                    'price' => $price,
                    'image' => $producto->image ?? 'default.png',
                    'quantity' => $item->quantity,
                    'stock_available' => $producto->stock_current,
                    'subtotal' => $subtotal
                ];
            }
        }

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

        $clientId = Auth::guard('clients')->id();
        CartItem::where('client_id', $clientId)
            ->where('product_id', $request->product_id)
            ->update(['quantity' => $request->quantity]);

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
        $clientId = Auth::guard('clients')->id();
        CartItem::where('client_id', $clientId)->where('product_id', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Producto eliminado del carrito',
            'cart_count' => $this->getCartCount(),
            'cart_total' => $this->getCartTotal()
        ]);
    }

    /**
     * Procesa el checkout: crea la venta desde cart_items, vacía el carrito del Client
     */
    public function checkout(Request $request)
    {
        $clientId = Auth::guard('clients')->id();
        $items = CartItem::where('client_id', $clientId)->with('product')->get();

        if ($items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'El carrito está vacío'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $subtotal = 0;
            $validatedItems = [];

            foreach ($items as $cartItem) {
                $product = $cartItem->product;

                if (!$product || $product->status !== 'active') {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'El producto "' . ($product->name ?? '') . '" ya no está disponible'
                    ], 400);
                }

                if ($product->stock_current < $cartItem->quantity) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Stock insuficiente para "' . $product->name . '". Disponible: ' . $product->stock_current
                    ], 400);
                }

                $price = $product->sale_price;
                $itemTotal = $price * $cartItem->quantity;
                $subtotal += $itemTotal;

                $validatedItems[] = [
                    'product' => $product,
                    'quantity' => $cartItem->quantity,
                    'price' => $price,
                    'total' => $itemTotal
                ];
            }

            $sale = Sale::create([
                'invoice_number' => (new Sale())->generateInvoiceNumber(),
                'customer_id' => null,
                'client_id' => $clientId,
                'seller_id' => null,
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

            CartItem::where('client_id', $clientId)->delete();

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
     * Obtiene el conteo de ítems en el carrito (solo para Client logueado).
     * Devuelve 0 si la tabla cart_items no existe aún.
     */
    private function getCartCount()
    {
        if (!Auth::guard('clients')->check()) {
            return 0;
        }
        try {
            return (int) CartItem::where('client_id', Auth::guard('clients')->id())->sum('quantity');
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Obtiene el total del carrito (solo para Client logueado).
     * Devuelve 0 si la tabla cart_items no existe aún.
     */
    private function getCartTotal()
    {
        if (!Auth::guard('clients')->check()) {
            return 0;
        }
        try {
            $items = CartItem::where('client_id', Auth::guard('clients')->id())->with('product')->get();
            $total = 0;
            foreach ($items as $item) {
                if ($item->product) {
                    $total += $item->product->sale_price * $item->quantity;
                }
            }
            return $total;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function clearCart()
    {
        $clientId = Auth::guard('clients')->id();

        if ($clientId) {
            CartItem::where('client_id', $clientId)->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Carrito vaciado exitosamente',
            'cart_count' => 0,
            'cart_total' => 0
        ]);
    }
}
