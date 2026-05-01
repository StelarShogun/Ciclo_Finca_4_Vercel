<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;
use App\Services\Admin\AdminPdfExportService;
use App\Services\Admin\AdminPdfExportLimits;
use App\Services\Admin\ReportExcelFilename;
use App\Services\Admin\ReportPdfFilename;
use App\Services\Admin\RegistryExcelExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsRegistryExportController extends Controller
{
    // Supported export slugs; any other value results in a 404 response.
    private const SLUGS = [
        'proveedores',
        'marcas',
        'pedidos-proveedores',
        'usuarios',
        'pedidos-clientes',
    ];

    public function download(Request $request, string $slug): Response|StreamedResponse
    {
        // Reject unknown slugs immediately.
        if (! in_array($slug, self::SLUGS, true)) {
            abort(404);
        }

        // Validate the requested export format; defaults to PDF.
        $format = strtolower((string) $request->query('format', 'pdf'));
        if (! in_array($format, ['pdf', 'excel'], true)) {
            abort(400, 'Formato no válido. Use pdf o excel.');
        }

        $effectiveRequest = $request->query('scope') === 'all' ? new Request() : $request;

        // Route each slug/format combination to its dedicated handler.
        return match ($slug) {
            'proveedores' => match ($format) {
                'pdf'   => $this->suppliersPdf($effectiveRequest),
                'excel' => $this->suppliersExcel($effectiveRequest),
            },
            'marcas' => match ($format) {
                'pdf'   => $this->brandsPdf($effectiveRequest),
                'excel' => $this->brandsExcel($effectiveRequest),
            },
            'pedidos-proveedores' => match ($format) {
                'pdf'   => $this->supplierOrdersPdf($effectiveRequest),
                'excel' => $this->supplierOrdersExcel($effectiveRequest),
            },
            'usuarios' => match ($format) {
                'pdf'   => $this->clientsPdf(),
                'excel' => $this->clientsExcel(),
            },
            'pedidos-clientes' => match ($format) {
                'pdf'   => $this->clientOrdersPdf($effectiveRequest),
                'excel' => $this->clientOrdersExcel($effectiveRequest),
            },
        };
    }

    // Returns the absolute path to the brand logo if it exists on disk, or null otherwise.
    private function resolvedLogoPath(): ?string
    {
        $path = public_path('assets/images/brand/logo-ciclo-finca-icon.png');

        return is_file($path) ? $path : null;
    }

    // =========================================================================
    // SUPPLIERS
    // =========================================================================

    // Builds the base query for suppliers, applying optional name and contact filters.
    private function suppliersBase(Request $request): Builder
    {
        $query = Supplier::query()->orderBy('name');

        if ($request->filled('name')) {
            $term = trim((string) $request->get('name'));
            $query->where('name', 'like', '%'.$term.'%');
        }
        if ($request->filled('contact')) {
            $term = trim((string) $request->get('contact'));
            $query->where('primary_contact', 'like', '%'.$term.'%');
        }

        return $query;
    }

    // Returns human-readable filter descriptions to display in the exported document header.
    private function suppliersCatalogFilterLines(Request $request): array
    {
        $lines = [];
        if ($request->filled('name')) {
            $lines[] = 'Nombre: '.$request->string('name');
        }
        if ($request->filled('contact')) {
            $lines[] = 'Contacto: '.$request->string('contact');
        }

        return $lines;
    }

    // Returns the column headers for the suppliers export table.
    private function suppliersHeaders(): array
    {
        return ['ID', 'Nombre', 'Contacto', 'Teléfono', 'Email', 'Dirección', 'Entrega (días)', 'Valoración', 'Estado'];
    }

    // Maps supplier records into a plain array of strings suitable for tabular export.
    // When $truncateAddress is true, long address strings are shortened to fit PDF columns.
    private function suppliersRows(Builder $base, int $limit, bool $truncateAddress = false): array
    {
        $data = [];
        foreach ((clone $base)->limit($limit)->get() as $s) {
            if (! $s instanceof Supplier) {
                continue;
            }
            $address = $truncateAddress
                ? Str::limit((string) ($s->address ?? ''), 80)
                : (string) ($s->address ?? '');
            $data[] = [
                (string) $s->supplier_id,
                $s->name,
                (string) ($s->primary_contact ?? ''),
                (string) ($s->phone ?? ''),
                (string) ($s->email ?? ''),
                $address,
                (string) ($s->delivery_time ?? ''),
                (string) ($s->rating ?? ''),
                (string) ($s->status ?? ''),
            ];
        }

        return $data;
    }

    // Generates and streams a PDF export of the suppliers list, applying the row limit defined in AdminPdfExportLimits.
    private function suppliersPdf(Request $request): Response
    {
        $base        = $this->suppliersBase($request);
        $total       = (clone $base)->count();
        $filterLines = $this->suppliersCatalogFilterLines($request);
        $max         = AdminPdfExportLimits::REGISTRY_MAX_ROWS;
        // Warn the user if the result set exceeds the PDF row cap.
        if ($total > $max) {
            $filterLines[] = 'Nota: el PDF incluye como máximo '.$max.' filas ('.$total.' proveedores coinciden).';
        }

        return $this->registryPdf(
            'Proveedores',
            'Listado de proveedores — Ciclo Finca 4',
            $filterLines,
            $this->suppliersHeaders(),
            $this->suppliersRows($base, $max, true),
            'proveedores'
        );
    }

    // Generates and streams an Excel export of the suppliers list.
    private function suppliersExcel(Request $request): StreamedResponse
    {
        $base        = $this->suppliersBase($request);
        $total       = (clone $base)->count();
        $filterLines = $this->suppliersCatalogFilterLines($request);
        $max         = AdminPdfExportLimits::REGISTRY_MAX_ROWS;
        // Warn the user if the result set exceeds the Excel row cap.
        if ($total > $max) {
            $filterLines[] = 'Nota: el Excel incluye como máximo '.$max.' filas ('.$total.' proveedores coinciden).';
        }

        return app(RegistryExcelExport::class)->download(
            'Proveedores',
            'Listado de proveedores — Ciclo Finca 4',
            $this->suppliersHeaders(),
            $this->suppliersRows($base, $max),
            $filterLines,
            ReportExcelFilename::make('proveedores'),
        );
    }

    // Streams a CSV export of suppliers using a database cursor to keep memory usage low.
    private function suppliersCsv(Request $request): StreamedResponse
    {
        return $this->streamRegistryCsv(
            'proveedores_'.now()->format('Y-m-d_His').'.csv',
            ['ID', 'Nombre', 'Contacto', 'Teléfono', 'Email', 'Dirección', 'Entrega_dias', 'Valoracion', 'Estado'],
            function (callable $emitRow) use ($request): void {
                foreach ($this->suppliersBase($request)->cursor() as $s) {
                    if (! $s instanceof Supplier) {
                        continue;
                    }
                    $emitRow([
                        $s->supplier_id,
                        $s->name,
                        $s->primary_contact,
                        $s->phone,
                        $s->email,
                        $s->address,
                        $s->delivery_time,
                        $s->rating,
                        $s->status,
                    ]);
                }
            }
        );
    }

    // =========================================================================
    // BRANDS
    // =========================================================================

    // Builds the base query for brands, applying an optional name filter.
    private function brandsBase(Request $request): Builder
    {
        $query = Brand::query()->orderBy('name');

        if ($request->filled('name')) {
            $term = trim((string) $request->get('name'));
            $query->where('name', 'like', '%'.$term.'%');
        }

        return $query;
    }

    // Returns human-readable filter descriptions to display in the exported document header.
    private function brandsCatalogFilterLines(Request $request): array
    {
        $lines = [];
        if ($request->filled('name')) {
            $lines[] = 'Nombre: '.$request->string('name');
        }

        return $lines;
    }

    // Returns the column headers for the brands export table.
    private function brandsHeaders(): array
    {
        return ['ID', 'Nombre'];
    }

    // Maps brand records into a plain array of strings suitable for tabular export.
    private function brandsRows(Builder $base, int $limit): array
    {
        $data = [];
        foreach ((clone $base)->limit($limit)->get() as $b) {
            if (! $b instanceof Brand) {
                continue;
            }
            $data[] = [(string) $b->id, $b->name];
        }

        return $data;
    }

    // Generates and streams a PDF export of the brands catalogue.
    private function brandsPdf(Request $request): Response
    {
        $base        = $this->brandsBase($request);
        $total       = (clone $base)->count();
        $filterLines = $this->brandsCatalogFilterLines($request);
        $max         = AdminPdfExportLimits::REGISTRY_MAX_ROWS;
        if ($total > $max) {
            $filterLines[] = 'Nota: el PDF incluye como máximo '.$max.' filas ('.$total.' marcas coinciden).';
        }

        return $this->registryPdf('Marcas', 'Catálogo de marcas — Ciclo Finca 4', $filterLines, $this->brandsHeaders(), $this->brandsRows($base, $max), 'marcas');
    }

    // Generates and streams an Excel export of the brands catalogue.
    private function brandsExcel(Request $request): StreamedResponse
    {
        $base        = $this->brandsBase($request);
        $total       = (clone $base)->count();
        $filterLines = $this->brandsCatalogFilterLines($request);
        $max         = AdminPdfExportLimits::REGISTRY_MAX_ROWS;
        if ($total > $max) {
            $filterLines[] = 'Nota: el Excel incluye como máximo '.$max.' filas ('.$total.' marcas coinciden).';
        }

        return app(RegistryExcelExport::class)->download(
            'Marcas',
            'Catálogo de marcas — Ciclo Finca 4',
            $this->brandsHeaders(),
            $this->brandsRows($base, $max),
            $filterLines,
            ReportExcelFilename::make('marcas'),
        );
    }

    // Streams a CSV export of brands using a database cursor to keep memory usage low.
    private function brandsCsv(Request $request): StreamedResponse
    {
        return $this->streamRegistryCsv(
            'marcas_'.now()->format('Y-m-d_His').'.csv',
            ['ID', 'Nombre'],
            function (callable $emitRow) use ($request): void {
                foreach ($this->brandsBase($request)->cursor() as $b) {
                    if (! $b instanceof Brand) {
                        continue;
                    }
                    $emitRow([$b->id, $b->name]);
                }
            }
        );
    }

    // =========================================================================
    // SUPPLIER ORDERS
    // =========================================================================

    // Returns human-readable filter descriptions to display in the exported document header.
    private function supplierOrderFilterLines(Request $request): array
    {
        $lines = [];
        if ($request->filled('state')) {
            $lines[] = 'Estado: '.$request->string('state');
        }
        if ($request->filled('date_from')) {
            $lines[] = 'Desde: '.$request->string('date_from');
        }
        if ($request->filled('date_to')) {
            $lines[] = 'Hasta: '.$request->string('date_to');
        }
        if ($request->filled('search')) {
            $lines[] = 'Búsqueda: '.$request->string('search');
        }

        return $lines;
    }

    // Builds the base query for supplier orders, normalising the date range and applying all active filters.
    private function supplierOrdersBase(Request $request): Builder
    {
        // Swap inverted date boundaries so the range is always chronologically correct.
        $dateFrom = $request->get('date_from');
        $dateTo   = $request->get('date_to');
        if ($dateFrom && $dateTo && $dateTo < $dateFrom) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $query = Order::query()->with(['supplier', 'orderItems'])->orderBy('date', 'desc');

        if ($request->filled('state')) {
            $query->where('state', $request->get('state'));
        }
        if ($dateFrom) {
            $query->whereDate('date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('date', '<=', $dateTo);
        }
        // Search by order number or supplier name.
        if ($request->filled('search')) {
            $search = trim((string) $request->get('search'));
            $query->where(function ($q) use ($search) {
                $q->where('num_order', 'like', '%'.$search.'%')
                    ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', '%'.$search.'%'));
            });
        }

        return $query;
    }

    // Returns the column headers for the supplier orders export table.
    private function supplierOrdersHeaders(): array
    {
        return ['Nº pedido', 'Proveedor', 'Fecha', 'Estado', 'Total', 'Productos (resumen)'];
    }

    // Maps supplier order records into a plain array of strings suitable for tabular export.
    // When $truncateSummary is true, the line-item summary is shortened to fit PDF columns.
    private function supplierOrdersRows(Builder $base, int $limit, bool $truncateSummary = false): array
    {
        $data = [];
        foreach ((clone $base)->limit($limit)->get() as $o) {
            if (! $o instanceof Order) {
                continue;
            }
            $supplierName = ($o->supplier instanceof Supplier) ? $o->supplier->name : '—';
            $dateStr      = $o->date !== null ? $o->date->format('d/m/Y H:i') : '';
            $summary      = $this->summarizeSupplierOrderLines($o);
            $data[] = [
                (string) $o->num_order,
                $supplierName,
                $dateStr,
                (string) $o->state,
                '₡'.number_format((float) $o->total, 0, ',', '.'),
                $truncateSummary ? Str::limit($summary, 120) : $summary,
            ];
        }

        return $data;
    }

    // Generates and streams a PDF export of supplier orders.
    private function supplierOrdersPdf(Request $request): Response
    {
        $base        = $this->supplierOrdersBase($request);
        $total       = (clone $base)->count();
        $filterLines = $this->supplierOrderFilterLines($request);
        $max         = AdminPdfExportLimits::REGISTRY_MAX_ROWS;
        if ($total > $max) {
            $filterLines[] = 'Nota: el PDF incluye como máximo '.$max.' filas ('.$total.' pedidos coinciden).';
        }

        return $this->registryPdf(
            'Pedidos a proveedores',
            'Pedidos de reposición — Ciclo Finca 4',
            $filterLines,
            $this->supplierOrdersHeaders(),
            $this->supplierOrdersRows($base, $max, true),
            'pedidos-proveedores'
        );
    }

    // Generates and streams an Excel export of supplier orders.
    private function supplierOrdersExcel(Request $request): StreamedResponse
    {
        $base        = $this->supplierOrdersBase($request);
        $total       = (clone $base)->count();
        $filterLines = $this->supplierOrderFilterLines($request);
        $max         = AdminPdfExportLimits::REGISTRY_MAX_ROWS;
        if ($total > $max) {
            $filterLines[] = 'Nota: el Excel incluye como máximo '.$max.' filas ('.$total.' pedidos coinciden).';
        }

        return app(RegistryExcelExport::class)->download(
            'Pedidos a proveedores',
            'Pedidos de reposición — Ciclo Finca 4',
            $this->supplierOrdersHeaders(),
            $this->supplierOrdersRows($base, $max),
            $filterLines,
            ReportExcelFilename::make('pedidos-proveedores'),
        );
    }

    // Streams a CSV export of supplier orders in chunks to avoid loading all records into memory at once.
    // Line items are serialised as a JSON array in the last column.
    private function supplierOrdersCsv(Request $request): StreamedResponse
    {
        $chunk = AdminPdfExportLimits::REGISTRY_CSV_CHUNK;

        return $this->streamRegistryCsv(
            'pedidos_proveedores_'.now()->format('Y-m-d_His').'.csv',
            ['Num_pedido', 'Proveedor', 'Fecha', 'Estado', 'Total', 'Lineas_pedido_JSON'],
            function (callable $emitRow) use ($request, $chunk): void {
                $this->supplierOrdersBase($request)->chunkById($chunk, function ($orders) use ($emitRow): void {
                    foreach ($orders as $o) {
                        if (! $o instanceof Order) {
                            continue;
                        }
                        $supplierName = ($o->supplier instanceof Supplier) ? $o->supplier->name : null;
                        $dateStr      = $o->date !== null ? $o->date->format('Y-m-d H:i:s') : null;
                        // Use already-loaded items when available to avoid an extra query per order.
                        $lines        = $o->relationLoaded('orderItems')
                            ? $o->orderItems
                            : OrderItem::query()->where('order_num_order', $o->num_order)->get();
                        $payload = $lines->map(fn (OrderItem $line) => [
                            'product_id' => (int) $line->product_id,
                            'name'       => $line->name,
                            'quantity'   => (int) $line->quantity,
                            'unit_price' => (float) $line->unit_price,
                            'total'      => (float) $line->total,
                        ])->values()->all();
                        $emitRow([
                            $o->num_order,
                            $supplierName,
                            $dateStr,
                            $o->state,
                            $o->total,
                            json_encode($payload, JSON_UNESCAPED_UNICODE),
                        ]);
                    }
                }, 'num_order');
            }
        );
    }

    // Builds a comma-separated summary of product names and quantities for a single supplier order.
    private function summarizeSupplierOrderLines(Order $order): string
    {
        $order->loadMissing('orderItems');
        if ($order->orderItems->isEmpty()) {
            return '';
        }
        $parts = [];
        foreach ($order->orderItems as $line) {
            if (! $line instanceof OrderItem) {
                continue;
            }
            $parts[] = $line->name.' × '.$line->quantity;
        }

        return implode(', ', $parts);
    }

    // =========================================================================
    // CLIENTS (web store accounts)
    // =========================================================================

    // Returns the column headers for the clients export table.
    private function clientsHeaders(): array
    {
        return ['ID', 'Nombre', 'Apellido 1', 'Apellido 2', 'Email', 'Activo', 'Proveedor', 'Email verificado'];
    }

    // Fetches all client records up to the configured row limit and maps them to export rows.
    private function clientsRows(): array
    {
        return Client::query()
            ->orderBy('name')
            ->limit(AdminPdfExportLimits::REGISTRY_MAX_ROWS)
            ->get()
            ->map(fn (Client $c): array => [
                (string) $c->user_id,
                $c->name,
                (string) ($c->first_surname ?? ''),
                (string) ($c->second_surname ?? ''),
                $c->gmail,
                $c->active ? 'Sí' : 'No',
                (string) $c->provider,
                $c->email_verified ? 'Sí' : 'No',
            ])
            ->values()
            ->all();
    }

    // Generates and streams a PDF export of registered web store clients.
    private function clientsPdf(): Response
    {
        return $this->registryPdf(
            'Usuarios (clientes web)',
            'Cuentas registradas en la tienda — Ciclo Finca 4',
            [],
            $this->clientsHeaders(),
            $this->clientsRows(),
            'usuarios-clientes'
        );
    }

    // Generates and streams an Excel export of registered web store clients.
    private function clientsExcel(): StreamedResponse
    {
        return app(RegistryExcelExport::class)->download(
            'Usuarios (clientes web)',
            'Cuentas registradas en la tienda — Ciclo Finca 4',
            $this->clientsHeaders(),
            $this->clientsRows(),
            [],
            ReportExcelFilename::make('usuarios-clientes'),
        );
    }

    // Streams a CSV export of clients using a database cursor to keep memory usage low.
    private function clientsCsv(): StreamedResponse
    {
        return $this->streamRegistryCsv(
            'usuarios_clientes_'.now()->format('Y-m-d_His').'.csv',
            ['user_id', 'nombre', 'apellido1', 'apellido2', 'email', 'activo', 'proveedor', 'email_verificado'],
            function (callable $emitRow): void {
                foreach (Client::query()->orderBy('name')->cursor() as $c) {
                    if (! $c instanceof Client) {
                        continue;
                    }
                    $emitRow([
                        $c->user_id,
                        $c->name,
                        $c->first_surname,
                        $c->second_surname,
                        $c->gmail,
                        $c->active ? '1' : '0',
                        $c->provider,
                        $c->email_verified ? '1' : '0',
                    ]);
                }
            }
        );
    }

    // =========================================================================
    // CLIENT ORDERS (web cart)
    // =========================================================================

    // Returns human-readable filter descriptions to display in the exported document header.
    private function clientOrderFilterLines(Request $request): array
    {
        $lines = [];
        if ($request->filled('status')) {
            $lines[] = 'Estado: '.$request->string('status');
        }
        if ($request->filled('search')) {
            $lines[] = 'Búsqueda: '.$request->string('search');
        }

        return $lines;
    }

    // Builds the base query for web cart orders, restricting to non-expired sales with valid statuses.
    private function clientOrdersBase(Request $request): Builder
    {
        $query = Sale::query()
            ->where(function ($q) {
                // Include both explicit web-cart orders and legacy records without a source tag.
                $q->where('order_source', 'web_cart')
                    ->orWhereNull('order_source');
            })
            ->whereIn('status', ['pending', 'completed', 'cancelled', 'refunded'])
            ->notExpired()
            ->with(['client', 'saleItems.product']);

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        // Search by sale ID, invoice number, or client name/email.
        if ($request->filled('search')) {
            $search = trim((string) $request->get('search'));
            $query->where(function ($q) use ($search) {
                $q->where('sale_id', 'like', '%'.$search.'%')
                    ->orWhere('invoice_number', 'like', '%'.$search.'%')
                    ->orWhereHas('client', function ($subQ) use ($search) {
                        $subQ->where('name', 'like', '%'.$search.'%')
                            ->orWhere('first_surname', 'like', '%'.$search.'%')
                            ->orWhere('gmail', 'like', '%'.$search.'%');
                    });
            });
        }

        return $query->orderBy('sale_date', 'desc');
    }

    // Returns the column headers for the client orders export table.
    private function clientOrdersHeaders(): array
    {
        return ['Factura / ID', 'Cliente', 'Fecha', 'Estado', 'Total', 'Ítems (resumen)'];
    }

    // Maps sale records into a plain array of strings suitable for tabular export.
    // When $truncate is true, long customer names and item summaries are shortened for PDF columns.
    private function clientOrdersRows(Builder $base, int $limit, bool $truncate = false): array
    {
        $data = [];
        foreach ((clone $base)->limit($limit)->get() as $sale) {
            if (! $sale instanceof Sale) {
                continue;
            }
            // Prefer the linked client's full name; fall back to the guest buyer name if present.
            $customer = $sale->client
                ? trim($sale->client->name.' '.($sale->client->first_surname ?? '').' '.($sale->client->second_surname ?? ''))
                : ($sale->buyer_name ?: '—');
            $items = $sale->saleItems->map(function (SaleItem $item): string {
                return ($item->product !== null ? $item->product->name : '?').' (×'.$item->quantity.')';
            })->implode(', ');
            $saleDate = $sale->sale_date;
            $data[]   = [
                (string) ($sale->invoice_number ?? '#'.$sale->sale_id),
                $truncate ? Str::limit($customer, 40) : $customer,
                $saleDate !== null ? $saleDate->format('d/m/Y H:i') : '',
                ucfirst((string) $sale->status),
                '₡'.number_format((float) $sale->total, 0, ',', '.'),
                $truncate ? Str::limit($items, 100) : $items,
            ];
        }

        return $data;
    }

    // Generates and streams a PDF export of client orders.
    private function clientOrdersPdf(Request $request): Response
    {
        $base        = $this->clientOrdersBase($request);
        $total       = (clone $base)->count();
        $filterLines = $this->clientOrderFilterLines($request);
        $max         = AdminPdfExportLimits::REGISTRY_MAX_ROWS;
        if ($total > $max) {
            $filterLines[] = 'Nota: el PDF incluye como máximo '.$max.' filas ('.$total.' pedidos coinciden).';
        }

        return $this->registryPdf(
            'Pedidos clientes',
            'Pedidos web / carrito — Ciclo Finca 4',
            $filterLines,
            $this->clientOrdersHeaders(),
            $this->clientOrdersRows($base, $max, true),
            'pedidos-clientes'
        );
    }

    // Generates and streams an Excel export of client orders.
    private function clientOrdersExcel(Request $request): StreamedResponse
    {
        $base        = $this->clientOrdersBase($request);
        $total       = (clone $base)->count();
        $filterLines = $this->clientOrderFilterLines($request);
        $max         = AdminPdfExportLimits::REGISTRY_MAX_ROWS;
        if ($total > $max) {
            $filterLines[] = 'Nota: el Excel incluye como máximo '.$max.' filas ('.$total.' pedidos coinciden).';
        }

        return app(RegistryExcelExport::class)->download(
            'Pedidos clientes',
            'Pedidos web / carrito — Ciclo Finca 4',
            $this->clientOrdersHeaders(),
            $this->clientOrdersRows($base, $max),
            $filterLines,
            ReportExcelFilename::make('pedidos-clientes'),
        );
    }

    // Streams a CSV export of client orders in chunks to avoid loading all records into memory at once.
    private function clientOrdersCsv(Request $request): StreamedResponse
    {
        $chunk = AdminPdfExportLimits::REGISTRY_CSV_CHUNK;

        return $this->streamRegistryCsv(
            'pedidos_clientes_'.now()->format('Y-m-d_His').'.csv',
            ['sale_id', 'invoice', 'cliente', 'email_cliente', 'fecha', 'estado', 'total', 'items_resumen'],
            function (callable $emitRow) use ($request, $chunk): void {
                $this->clientOrdersBase($request)->chunkById($chunk, function ($sales) use ($emitRow): void {
                    foreach ($sales as $sale) {
                        if (! $sale instanceof Sale) {
                            continue;
                        }
                        // Prefer the linked client's full name; fall back to the guest buyer name.
                        $customer = $sale->client
                            ? trim($sale->client->name.' '.($sale->client->first_surname ?? '').' '.($sale->client->second_surname ?? ''))
                            : ($sale->buyer_name ?: '');
                        $email = $sale->client ? $sale->client->gmail : ($sale->buyer_email ?? '');
                        $items = $sale->saleItems->map(function (SaleItem $item): string {
                            return ($item->product !== null ? $item->product->name : '?').' (x'.$item->quantity.')';
                        })->implode(', ');
                        $saleDate = $sale->sale_date;
                        $emitRow([
                            $sale->sale_id,
                            $sale->invoice_number,
                            $customer,
                            $email,
                            $saleDate !== null ? $saleDate->format('Y-m-d H:i:s') : null,
                            $sale->status,
                            $sale->total,
                            $items,
                        ]);
                    }
                }, 'sale_id');
            }
        );
    }

    // =========================================================================
    // SHARED HELPERS
    // =========================================================================

    // Renders the registry Blade view as a PDF and returns it as a downloadable response.
    private function registryPdf(
        string $title,
        string $subtitle,
        array $filterLines,
        array $headers,
        array $rows,
        string $filenameSlug
    ): Response {
        return app(AdminPdfExportService::class)->download('admin.exports.registry-table-pdf', [
            'pdfTitle'     => $title,
            'pdfSubtitle'  => $subtitle,
            'logoPath'     => $this->resolvedLogoPath(),
            'filterLines'  => $filterLines,
            'generatedFor' => 'Administración',
            'headers'      => $headers,
            'rows'         => $rows,
        ], $filenameSlug);
    }

    // Streams a UTF-8 BOM-prefixed CSV file row by row via a producer callback, avoiding full in-memory buffering.
    // The separator is a semicolon to maximise compatibility with European spreadsheet locales.
    private function streamRegistryCsv(string $filename, array $headerRow, callable $producer): StreamedResponse
    {
        $httpHeaders = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($headerRow, $producer): void {
            $file = fopen('php://output', 'w');
            if ($file === false) {
                return;
            }
            // Write the UTF-8 BOM so Excel opens the file without encoding issues.
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, $headerRow, ';');
            // Provide a type-safe row emitter to the producer callback.
            $emitRow = static function (array $row) use ($file): void {
                fputcsv($file, array_map(fn ($v) => $v === null ? '' : $v, $row), ';');
            };
            $producer($emitRow);
            fclose($file);
        };

        return response()->stream($callback, 200, $httpHeaders);
    }
}