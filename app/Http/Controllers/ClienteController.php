<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class ClienteController extends Controller
{
    /**
     * Muestra la página home con productos destacados y categorías
     */
    public function home()
    {
        // Obtener productos destacados (activos, con stock, limitados)
        $productosDestacados = Producto::with(['categoria'])
            ->where('estado', 'activo')
            ->where('stock_actual', '>', 0)
            ->orderBy('fecha_creacion', 'desc')
            ->limit(8)
            ->get();

        // Obtener categorías principales
        $categorias = Categoria::whereNull('categoria_padre_id')
            ->orderBy('nombre')
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
        $query = Producto::with(['categoria'])
            ->where('estado', 'activo')
            ->where('stock_actual', '>', 0);

        // Búsqueda por nombre o descripción
        if ($request->filled('buscar')) {
            $searchTerm = $request->buscar;
            $query->where(function($q) use ($searchTerm) {
                $q->where('nombre', 'like', '%' . $searchTerm . '%')
                  ->orWhere('descripcion', 'like', '%' . $searchTerm . '%');
            });
        }

        // Filtro por categoría
        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }

        // Filtro por rango de precio
        if ($request->filled('precio_min')) {
            $query->where('precio_venta', '>=', $request->precio_min);
        }
        if ($request->filled('precio_max')) {
            $query->where('precio_venta', '<=', $request->precio_max);
        }

        // Ordenamiento
        $sort = $request->get('ordenar', 'fecha_creacion');
        $order = $request->get('direccion', 'desc');
        
        if ($sort === 'precio') {
            $query->orderBy('precio_venta', $order);
        } elseif ($sort === 'nombre') {
            $query->orderBy('nombre', $order);
        } else {
            $query->orderBy('fecha_creacion', $order);
        }

        // Paginación
        $perPage = $request->get('por_pagina', 12);
        $productos = $query->paginate($perPage)->withQueryString();

        // Obtener categorías para el filtro
        $categorias = Categoria::whereNull('categoria_padre_id')
            ->orderBy('nombre')
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
        $producto = Producto::with(['categoria', 'proveedor'])
            ->where('estado', 'activo')
            ->findOrFail($id);

        // Productos relacionados (misma categoría)
        $productosRelacionados = Producto::with(['categoria'])
            ->where('categoria_id', $producto->categoria_id)
            ->where('producto_id', '!=', $producto->producto_id)
            ->where('estado', 'activo')
            ->where('stock_actual', '>', 0)
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
        $request->validate([
            'producto_id' => 'required|exists:productos,producto_id',
            'cantidad' => 'required|integer|min:1'
        ]);

        $producto = Producto::findOrFail($request->producto_id);

        // Validar que el producto esté activo y tenga stock
        if ($producto->estado !== 'activo') {
            return response()->json([
                'success' => false,
                'message' => 'Este producto no está disponible'
            ], 400);
        }

        if ($producto->stock_actual < $request->cantidad) {
            return response()->json([
                'success' => false,
                'message' => 'Stock insuficiente. Disponible: ' . $producto->stock_actual
            ], 400);
        }

        // Obtener carrito actual
        $cart = Session::get('carrito', []);

        // Verificar si el producto ya está en el carrito
        $productoIndex = null;
        foreach ($cart as $index => $item) {
            if ($item['producto_id'] == $request->producto_id) {
                $productoIndex = $index;
                break;
            }
        }

        if ($productoIndex !== null) {
            // Actualizar cantidad
            $nuevaCantidad = $cart[$productoIndex]['cantidad'] + $request->cantidad;
            
            if ($nuevaCantidad > $producto->stock_actual) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock insuficiente. Disponible: ' . $producto->stock_actual
                ], 400);
            }

            $cart[$productoIndex]['cantidad'] = $nuevaCantidad;
        } else {
            // Agregar nuevo producto
            $cart[] = [
                'producto_id' => $producto->producto_id,
                'nombre' => $producto->nombre,
                'precio' => $producto->precio_venta,
                'imagen' => $producto->imagen ?? 'default.png',
                'cantidad' => $request->cantidad,
                'stock_disponible' => $producto->stock_actual
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
            $producto = Producto::find($item['producto_id']);
            if ($producto && $producto->estado === 'activo') {
                $subtotal = $item['precio'] * $item['cantidad'];
                $total += $subtotal;
                
                $cartItems[] = [
                    'producto_id' => $producto->producto_id,
                    'nombre' => $producto->nombre,
                    'precio' => $item['precio'],
                    'imagen' => $producto->imagen ?? 'default.png',
                    'cantidad' => $item['cantidad'],
                    'stock_disponible' => $producto->stock_actual,
                    'subtotal' => $subtotal
                ];
            }
        }

        // Actualizar carrito en sesión (eliminar productos que ya no están activos)
        Session::put('carrito', array_map(function($item) {
            return [
                'producto_id' => $item['producto_id'],
                'nombre' => $item['nombre'],
                'precio' => $item['precio'],
                'imagen' => $item['imagen'],
                'cantidad' => $item['cantidad'],
                'stock_disponible' => $item['stock_disponible']
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
        $request->validate([
            'producto_id' => 'required|exists:productos,producto_id',
            'cantidad' => 'required|integer|min:1'
        ]);

        $producto = Producto::findOrFail($request->producto_id);

        if ($producto->estado !== 'activo') {
            return response()->json([
                'success' => false,
                'message' => 'Este producto ya no está disponible'
            ], 400);
        }

        if ($producto->stock_actual < $request->cantidad) {
            return response()->json([
                'success' => false,
                'message' => 'Stock insuficiente. Disponible: ' . $producto->stock_actual
            ], 400);
        }

        $cart = Session::get('carrito', []);

        foreach ($cart as $index => $item) {
            if ($item['producto_id'] == $request->producto_id) {
                $cart[$index]['cantidad'] = $request->cantidad;
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
            return $item['producto_id'] != $id;
        });

        Session::put('carrito', array_values($cart));

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Producto eliminado del carrito',
                'cart_count' => $this->getCartCount(),
                'cart_total' => $this->getCartTotal()
            ]);
        }

        return redirect()->route('clientes.carrito')->with('status', 'Producto eliminado del carrito');
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
            $total += $item['precio'] * $item['cantidad'];
        }

        return $total;
    }
}

