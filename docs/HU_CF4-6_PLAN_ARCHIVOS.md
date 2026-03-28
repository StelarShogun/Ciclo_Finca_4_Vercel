# HU CF4-6 — Lista concreta de archivos y cambios

Solo tocar lo indicado. No modificar estructura HTML/CSS del carrito ni el mensaje flotante ni el flujo JS existente más allá de lo indicado.

---

## 1. CREAR (archivos nuevos)

### 1.1 Migración: tabla `cart_items`
- **Archivo:** `database/migrations/YYYY_MM_DD_HHMMSS_create_cart_items_table.php`
- **Acción:** Crear migración con:
  - `id` bigIncrements
  - `client_id` unsignedBigInteger, FK a `client_table.user_id`, index
  - `product_id` unsignedBigInteger, FK a `products.product_id`, index
  - `quantity` unsignedInteger, default 1
  - `timestamps`
  - Índice único `(client_id, product_id)` para evitar duplicados por producto

### 1.2 Migración: agregar `client_id` a `sales` y hacer `customer_id`/`seller_id` nullables
- **Archivo:** `database/migrations/YYYY_MM_DD_HHMMSS_add_client_id_to_sales_table.php`
- **Acción:**
  - Agregar columna `client_id` nullable, unsignedBigInteger, FK a `client_table.user_id`
  - Hacer `customer_id` nullable (ALTER)
  - Hacer `seller_id` nullable (ALTER)
  - (Si la BD ya tiene nullables, omitir los ALTER correspondientes.)

### 1.3 Modelo CartItem
- **Archivo:** `app/Models/CartItem.php`
- **Acción:** Crear modelo con:
  - `$table = 'cart_items'`
  - `$fillable = ['client_id', 'product_id', 'quantity']`
  - `belongsTo(Client::class)` y `belongsTo(Product::class)`

---

## 2. MODIFICAR (solo lo indicado)

### 2.1 Rutas del carrito
- **Archivo:** `routes/web.php`
- **Líneas:** 42-49 (grupo de rutas del carrito)
- **Cambio:** Sustituir `middleware(['auth'])` por `middleware(['auth:clients'])`.
- **Nada más:** no tocar otras rutas.

### 2.2 ClienteController — uso de BD y guard `clients`
- **Archivo:** `app/Http/Controllers/ClienteController.php`
- **Cambios:**
  - **Al inicio:** `use App\Models\CartItem;` y `use App\Models\Client;` (si hace falta).
  - **addToCart:** Obtener `client_id = Auth::guard('clients')->id()`. Dejar de usar `Session::get('carrito')`. Crear o actualizar fila en `CartItem` (por client_id + product_id). Devolver JSON con `cart_count` desde `getCartCount()`.
  - **cart():** Obtener ítems de `CartItem::where('client_id', Auth::guard('clients')->id())->with('product')`. Construir array `$cartItems` y `$total` desde esos registros. Pasar a la vista igual que ahora (misma estructura de datos para la vista).
  - **updateCart:** Actualizar `quantity` en `CartItem` donde `client_id` + `product_id`. Sin sesión.
  - **removeFromCart($id):** Borrar `CartItem` donde `client_id` y `product_id = $id`. Sin sesión.
  - **checkout:** Leer ítems de `CartItem` del cliente. Crear `Sale` con `client_id = Auth::guard('clients')->id()`, `customer_id = null`, `seller_id = null`. Crear `SaleItem` y descontar stock. Borrar todos los `CartItem` del cliente. Devolver JSON éxito (sin tocar sesión para ítems).
  - **getCartCount():** `CartItem::where('client_id', Auth::guard('clients')->id())->sum('quantity')`.
  - **getCartTotal():** Calcular desde `CartItem` del cliente (con precios del Product), o eliminar si ya no se usa.
- **No tocar:** Lógica de validación de producto activo/stock; estructura de respuesta JSON; nombres de rutas.

### 2.3 Vistas: condición “logueado” = Client
- **Archivo:** `resources/views/clientes/layouts/app.blade.php`
  - **Línea ~47:** Sustituir `@auth` por `@if(Auth::guard('clients')->check())` para el enlace al carrito y el `@else` que ya tiene el botón invitado y el bloque de `session('client_id')`.
- **Archivo:** `resources/views/clientes/home.blade.php`
  - **Línea ~53:** Sustituir `@auth` por `@if(Auth::guard('clients')->check())` para el botón “Agregar al carrito” y el `@else` con botón invitado.
- **Archivo:** `resources/views/clientes/catalogo.blade.php`
  - **Línea ~140:** Mismo cambio: `@auth` → `@if(Auth::guard('clients')->check())`.
- **Archivo:** `resources/views/clientes/producto.blade.php`
  - **Línea ~66:** Mismo cambio: `@auth` → `@if(Auth::guard('clients')->check())`.
- **No tocar:** HTML del carrito, estilos, mensajes, botones de cantidad ni el contenido del mensaje flotante.

### 2.4 Modelo Client
- **Archivo:** `app/Models/Client.php`
- **Acción:** Agregar `hasMany(CartItem::class)` (y `use App\Models\CartItem` si hace falta).
- **Nada más:** no tocar fillable ni tabla.

### 2.5 Modelo Sale
- **Archivo:** `app/Models/Sale.php`
- **Acción:** Agregar `client_id` a `$fillable`. Agregar relación `client(): BelongsTo` a `Client::class` con FK `client_id` y key `user_id`.
- **Nada más:** no tocar customer(), seller(), ni lógica de vigencia.

### 2.6 Panel admin — listado de ventas
- **Archivo:** `app/Http/Controllers/SalesController.php`
  - **index:** En el `with()`, agregar `'client'` además de `'customer', 'saleItems.product', 'seller'`. En el `if ($search)`, incluir búsqueda por `client` (p. ej. nombre o gmail) cuando exista relación client.
- **Archivo:** `resources/views/sales/index.blade.php`
  - **Línea ~152 (columna Cliente):** Sustituir por: si `$sale->client_id` existe, mostrar nombre del Client (p. ej. `$sale->client->name ?? ''` y apellidos); si no, mostrar `$sale->customer->nombre ?? 'N/A'` y apellido como ahora. Evitar acceder a `$sale->customer` cuando `customer_id` sea null (comprobar relación).
  - **Modal detalle / JS (línea ~446):** Si la venta tiene `client`, mostrar nombre del client; si no, customer como ahora. Ajustar `customerName` para que no falle cuando `customer` sea null.
- **No tocar:** Formulario de nueva venta (mostrador), filtros, KPIs, acciones Completar/Cancelar/Reembolsar.

### 2.7 Redirección cuando no está autenticado (guard clients)
- **Archivo:** `app/Exceptions/Handler.php` o donde se configure la redirección de `Authenticate` para el guard `clients`.
- **Acción:** Asegurar que las peticiones no autenticadas al guard `clients` redirijan a `route('login.show')` (o `/login`). Si ya redirigen por defecto a una ruta llamada `login`, verificar que sea la de clientes; si no, configurar la redirección solo para el guard `clients`.
- **Alternativa:** Si se usa middleware estándar `auth:clients`, comprobar en `bootstrap/app.php` o en el middleware que la redirección para usuarios no autenticados del guard `clients` apunte a la pantalla de login de clientes.

---

## 3. NO TOCAR

- **resources/views/clientes/carrito.blade.php:** Estructura HTML, clases, botones “Continuar comprando” y “Confirmar Compra”, lista de ítems, totales. Solo se alimenta con los mismos datos desde el controller (que pasarán a venir de BD).
- **resources/js/pages/clientes.js:** Mensaje flotante exacto después de confirmar compra, texto de SweetAlert para invitados. Solo asegurar que las llamadas a los endpoints sigan siendo las mismas (no cambiar URLs ni métodos).
- **Rutas públicas:** `/`, `/catalog`, `/product/{id}`, `/login`, `/logout`. No cambiar.
- **ClientUserController:** No tocar.
- **Formulario “Nueva Venta” en panel admin:** No hacer cliente opcional en esta tarea (queda para otra HU del módulo de ventas).
- **Migraciones existentes:** No modificar `2026_02_14_000001_refactor_sales_module_to_english.php` ni otras migraciones ya ejecutadas.

---

## 4. ORDEN SUGERIDO DE IMPLEMENTACIÓN

1. Crear migración `cart_items` y migración `add_client_id_to_sales`.
2. Ejecutar migraciones.
3. Crear modelo `CartItem` y relaciones en `Client` y `Sale`.
4. Cambiar rutas a `auth:clients`.
5. Ajustar `ClienteController` (addToCart, cart, updateCart, removeFromCart, checkout, getCartCount, getCartTotal).
6. Cambiar vistas (app, home, catalogo, producto) de `@auth` a `Auth::guard('clients')->check()`.
7. Ajustar `SalesController::index` y vista `sales/index` para mostrar cliente desde `client` cuando exista y no fallar con `customer` null.
8. Revisar redirección de no autenticado para guard `clients`.

---

## 5. VERIFICACIÓN RÁPIDA

- Cliente (login en `/login`) puede agregar al carrito, ver carrito, actualizar cantidades, eliminar ítems, confirmar compra.
- Carrito persiste al cerrar navegador (datos en `cart_items`).
- Tras confirmar, venta en admin con estado “Pendiente”, cliente = Client (nombre/gmail), vendedor = “—” o “Venta web”.
- Invitado ve modal “¿Ir a login?” al tocar Agregar o Carrito; no redirección directa.
- Usuario admin (guard web) no ve botón Agregar ni enlace al carrito en la tienda (opcional: puede seguir pudiendo acceder por URL con auth:clients no; con auth:clients solo Client puede entrar a /cart).
