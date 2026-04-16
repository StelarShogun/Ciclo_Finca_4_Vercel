<?php

namespace App\Console\Commands;

use App\Models\AdminUser;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Siembra ventas de demostración para pruebas manuales (reportes, listado de ventas).
 * Usa productos del catálogo real (precio de venta del producto); si hace falta diversidad,
 * crea productos extra con precios coherentes con el rango del inventario (marcados en descripción).
 * Facturas: prefijo BULK-MANUAL-.
 */
class SeedBulkSalesManualCommand extends Command
{
    private const INVOICE_PREFIX = 'BULK-MANUAL-';

    /** Marca en descripción para productos creados solo como apoyo al seed (se borran con --wipe). */
    private const SEED_PRODUCT_MARKER = '[seed-bulk-manual]';

    /** Lote antiguo a eliminar con --wipe. */
    private const LEGACY_DEMO_NAME_PREFIX = 'DEMO-BULK-VENTAS-';

    protected $signature = 'sales:seed-bulk-manual
                            {--wipe : Elimina ventas BULK-MANUAL-, restaura stock de esas líneas, y borra restos demo/seed }
                            {--count=100 : Número de ventas a crear }
                            {--min-products=28 : Mínimo de productos distintos en el pool (se usan reales + extras si hace falta) }
                            {--admin= : Gmail del administrador vendedor (por defecto el primero en la tabla) }
                            {--force : Permitir ejecutar fuera de entorno local/testing }';

    protected $description = 'Crea ventas de prueba usando productos reales (precio sale_price); 5–15 líneas por venta; fechas en los últimos 90 días.';

    public function handle(): int
    {
        if (! app()->environment('local', 'testing') && ! $this->option('force')) {
            $this->error('Por seguridad solo se ejecuta en local/testing. Usa --force en otros entornos (no recomendado en producción).');

            return 1;
        }

        if (! $this->ensureDatabaseConnection()) {
            return 1;
        }

        $count = max(1, min(500, (int) $this->option('count')));
        $minProducts = max(5, min(80, (int) $this->option('min-products')));

        $adminGmail = $this->option('admin');
        $admin = $adminGmail
            ? AdminUser::where('gmail', $adminGmail)->first()
            : AdminUser::query()->orderBy('user_id')->first();

        if (! $admin) {
            $this->error('No hay administrador. Crea uno o pasa --admin=tu@correo.com');

            return 1;
        }

        if ($this->option('wipe')) {
            $this->wipeBulkSalesAndRestoreStock();
            $this->purgeLegacyDemoProducts();
            $this->purgeSeedHelperProducts();
        }

        $products = $this->resolveProductPool($minProducts);
        if ($products->count() < 5) {
            $this->error('Hace falta al menos 5 productos activos con stock y precio > 0 en el catálogo.');

            return 1;
        }

        $this->info("Administrador vendedor: {$admin->gmail} (ID {$admin->user_id})");
        $this->info('Productos en el pool: '.$products->count().' (catálogo real + extras si se generaron).');
        $this->newLine();

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $now = Carbon::now();
        $start90 = $now->copy()->subDays(89)->startOfDay();

        DB::transaction(function () use ($count, $admin, $products, $start90, $now, $bar) {
            for ($i = 0; $i < $count; $i++) {
                $saleDate = $this->randomSaleDateBetween($start90, $now, $i);
                $lineCount = random_int(5, 15);
                $lines = $this->buildRandomLinesFromCatalog($products, $lineCount);

                $subtotal = 0.0;
                foreach ($lines as $line) {
                    $subtotal += $line['quantity'] * $line['unit_price'];
                }
                $subtotal = round($subtotal, 2);
                $iva = 0.0;
                $total = $subtotal;

                $sale = Sale::create([
                    'invoice_number' => self::INVOICE_PREFIX.str_pad((string) ($i + 1), 5, '0', STR_PAD_LEFT).'-'.substr(sha1((string) microtime(true).$i), 0, 6),
                    'customer_id' => null,
                    'client_id' => null,
                    'seller_id' => null,
                    'seller_admin_id' => $admin->user_id,
                    'subtotal' => $subtotal,
                    'iva' => $iva,
                    'discount' => 0,
                    'total' => $total,
                    'payment_method' => 'cash',
                    'payment_reference' => null,
                    'status' => 'completed',
                    'notes' => 'Sembrado: php artisan sales:seed-bulk-manual',
                    'sale_date' => $saleDate,
                    'buyer_name' => null,
                    'buyer_email' => null,
                    'order_source' => 'walk_in',
                ]);

                foreach ($lines as $line) {
                    $qty = $line['quantity'];
                    $unit = $line['unit_price'];
                    $lineTotal = round($qty * $unit, 2);
                    SaleItem::create([
                        'sale_id' => $sale->sale_id,
                        'product_id' => $line['product_id'],
                        'quantity' => $qty,
                        'unit_price' => $unit,
                        'unit_discount' => 0,
                        'total' => $lineTotal,
                    ]);
                    Product::query()->where('product_id', $line['product_id'])->decrement('stock_current', $qty);
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Listo: {$count} ventas con 5–15 líneas; precios alineados al catálogo; stock descontado como en ventas reales.");
        $this->line('Revisa Reportes → Productos más vendidos o Ventas. Para deshacer ventas sembradas: <fg=cyan>php artisan sales:seed-bulk-manual --wipe</>');

        return 0;
    }

    /**
     * Quita ventas sembradas y devuelve unidades al inventario antes de borrar líneas.
     */
    private function wipeBulkSalesAndRestoreStock(): void
    {
        $ids = Sale::query()
            ->where('invoice_number', 'like', self::INVOICE_PREFIX.'%')
            ->pluck('sale_id');

        if ($ids->isEmpty()) {
            $this->warn('No había ventas con prefijo '.self::INVOICE_PREFIX.'.');

            return;
        }

        $items = SaleItem::query()
            ->whereIn('sale_id', $ids)
            ->get(['product_id', 'quantity']);

        foreach ($items as $row) {
            Product::query()->where('product_id', $row->product_id)->increment('stock_current', (int) $row->quantity);
        }

        $n = $ids->count();
        SaleItem::query()->whereIn('sale_id', $ids)->delete();
        Sale::query()->whereIn('sale_id', $ids)->delete();
        $this->info("Eliminadas {$n} ventas BULK-MANUAL- y restaurado stock de sus líneas.");
    }

    /** Elimina productos del formato antiguo DEMO-BULK-VENTAS-* que ya no tengan líneas de venta. */
    private function purgeLegacyDemoProducts(): void
    {
        $deleted = Product::query()
            ->where('name', 'like', self::LEGACY_DEMO_NAME_PREFIX.'%')
            ->whereDoesntHave('saleItems')
            ->delete();

        if ($deleted > 0) {
            $this->info("Eliminados {$deleted} productos legacy (".self::LEGACY_DEMO_NAME_PREFIX.'*).');
        }

        $remaining = Product::query()->where('name', 'like', self::LEGACY_DEMO_NAME_PREFIX.'%')->count();
        if ($remaining > 0) {
            $this->warn("Siguen {$remaining} productos legacy con ventas enlazadas; bórralos a mano o limpia esas ventas.");
        }
    }

    /** Elimina productos creados como apoyo (marcador en descripción) que ya no tengan líneas. */
    private function purgeSeedHelperProducts(): void
    {
        $marker = '%'.self::SEED_PRODUCT_MARKER.'%';
        $ids = Product::query()
            ->where('description', 'like', $marker)
            ->whereDoesntHave('saleItems')
            ->pluck('product_id');

        if ($ids->isEmpty()) {
            return;
        }

        $deleted = Product::query()->whereIn('product_id', $ids)->delete();
        $this->info("Eliminados {$deleted} productos auxiliares del seed (marcador en descripción).");
    }

    /**
     * @return Collection<int, Product>
     */
    private function resolveProductPool(int $targetMin): Collection
    {
        $base = Product::query()
            ->where(function ($q) {
                $q->whereRaw('LOWER(TRIM(COALESCE(status, ""))) IN (?, ?)', ['active', 'activo']);
            })
            ->where('stock_current', '>', 0)
            ->where('sale_price', '>', 0)
            ->orderByDesc('stock_current')
            ->limit(200)
            ->get();

        if ($base->count() >= $targetMin) {
            return $base->shuffle()->values();
        }

        $stats = Product::query()
            ->where('sale_price', '>', 0)
            ->selectRaw('MIN(sale_price) as mn, MAX(sale_price) as mx')
            ->first();

        $minP = (float) ($stats->mn ?? 5000);
        $maxP = (float) ($stats->mx ?? 80000);
        if ($minP < 500) {
            $minP = 500;
        }
        if ($maxP < $minP * 1.05) {
            $maxP = $minP * 1.5;
        }
        if ($maxP > 2_000_000) {
            $maxP = 2_000_000;
        }

        $categoryId = Category::query()->inRandomOrder()->value('category_id')
            ?? Category::query()->value('category_id');
        $supplierId = Supplier::query()->inRandomOrder()->value('supplier_id')
            ?? Supplier::query()->value('supplier_id');

        if (! $categoryId || ! $supplierId) {
            $this->warn('No se pudieron crear productos auxiliares: falta categoría o proveedor en la BD.');

            return $base->shuffle()->values();
        }

        $needed = $targetMin - $base->count();
        $labels = $this->realisticAccessoryNames();

        $created = collect();
        for ($k = 0; $k < $needed; $k++) {
            $label = $labels[$k % count($labels)].' — variante '.($k + 1);
            $t = $k / max(1, $needed - 1);
            $salePrice = round($minP + ($maxP - $minP) * $t, 2);
            $purchasePrice = round($salePrice * (0.55 + (mt_rand(0, 15) / 100)), 2);

            $created->push(Product::create([
                'category_id' => $categoryId,
                'supplier_id' => $supplierId,
                'name' => $label,
                'description' => 'Artículo generado para diversificar pruebas de reportes. '.self::SEED_PRODUCT_MARKER,
                'purchase_price' => min($purchasePrice, $salePrice * 0.95),
                'sale_price' => $salePrice,
                'stock_current' => 800 + ($k * 17) % 400,
                'stock_minimum' => 2,
                'status' => 'active',
            ]));
        }

        return $base->concat($created)->shuffle()->values();
    }

    /**
     * @return list<string>
     */
    private function realisticAccessoryNames(): array
    {
        return [
            'Cinta de manillar cork 3 mm',
            'Pedales mixtos aluminio con calas',
            'Portabidón carbono mate',
            'Fundas sillín gel urbano',
            'Cadena 11v 126 eslabones',
            'Cámara 29×2.125 válvula Presta',
            'Luz delantera USB 400 lm',
            'Luz trasera COB recargable',
            'Candado U 14 mm con soporte',
            'Mini bomba telescópica 120 psi',
            'Multiherramienta 12 funciones',
            'Calas carretera flotantes 6°',
            'Casco MIPS talla M',
            'Guantes dedo largo invierno',
            'Protector vaina neopreno',
            'Termo bidón 650 ml',
            'Bolsa sillín impermeable 1,2 L',
            'Espejo retrovisor manillar',
            'Timbre clásico latón',
            'Cubre zapatillas neoprene',
            'Gafas fotocromáticas sport',
            'Rodillos entrenamiento plegables',
            'Rodillo guía cadena 11v',
            'Pastillas freno disco resina',
            'Líquido sellante 240 ml',
            'Kit parches sin pegamento',
            'Grasa cerámica tubo 100 g',
            'Lubricante cadena condiciones secas',
            'Desengrasante biodegradable 500 ml',
        ];
    }

    /**
     * @param  Collection<int, Product>  $pool
     * @return array<int, array{product_id: int, quantity: int, unit_price: float}>
     */
    private function buildRandomLinesFromCatalog(Collection $pool, int $lineCount): array
    {
        $ids = $pool->pluck('product_id')->unique()->values();
        if ($ids->isEmpty()) {
            throw new \RuntimeException('El pool de productos está vacío.');
        }

        $lines = [];
        /** @var array<int, int> $reserved Unidades ya asignadas a esta venta por product_id */
        $reserved = [];
        $attempts = 0;
        $maxAttempts = $lineCount * 40;

        while (count($lines) < $lineCount && $attempts < $maxAttempts) {
            $attempts++;
            $pid = (int) $ids[$attempts % $ids->count()];
            $product = Product::query()->find($pid);
            if (! $product) {
                continue;
            }

            $stock = (int) $product->stock_current;
            $already = $reserved[$pid] ?? 0;
            $available = $stock - $already;
            if ($available < 1) {
                continue;
            }

            $basePrice = (float) $product->sale_price;
            $jitter = 1 + (mt_rand(-25, 25) / 1000);
            $unit = round(max(1, $basePrice * $jitter), 2);

            $maxQty = min(6, $available);
            $qty = random_int(1, $maxQty);
            $reserved[$pid] = $already + $qty;

            $lines[] = [
                'product_id' => $pid,
                'quantity' => $qty,
                'unit_price' => $unit,
            ];
        }

        if (count($lines) < 5) {
            throw new \RuntimeException('No hay suficiente stock en el pool para armar una venta de al menos 5 líneas. Ejecuta con --wipe o aumenta stock en catálogo.');
        }

        return $lines;
    }

    private function randomSaleDateBetween(Carbon $from, Carbon $to, int $salt): Carbon
    {
        $fromSec = $from->getTimestamp();
        $toSec = $to->getTimestamp();
        $span = max(1, $toSec - $fromSec);
        $offset = (int) (abs(crc32((string) $salt.'-'.microtime())) % $span);

        return $from->copy()->addSeconds($offset);
    }

    private function ensureDatabaseConnection(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            $host = (string) config('database.connections.'.config('database.default').'.host', '');

            $this->error('No se pudo conectar a la base de datos.');
            $this->line($msg);

            if (str_contains($host, 'db_ciclo') || str_contains($msg, 'db_ciclo') || str_contains($msg, 'getaddrinfo')) {
                $this->newLine();
                $this->warn('Tu DB_HOST apunta a un contenedor Docker (p. ej. db_ciclo). Ese nombre no resuelve en la terminal del host.');
                $this->line('Ejecuta artisan dentro del contenedor de la app:');
                $this->line('  <fg=cyan>docker compose exec app_ciclo php artisan sales:seed-bulk-manual</>');
                $this->newLine();
                $this->line('Alternativa en el host: en .env usa DB_HOST=127.0.0.1 y DB_PORT=3307 (puerto publicado por este compose para MySQL).');
            }

            return false;
        }
    }
}
