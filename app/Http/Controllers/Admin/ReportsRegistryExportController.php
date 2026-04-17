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
use App\Services\Admin\AdminPdfExportLimits;
use App\Services\Admin\ReportPdfFilename;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsRegistryExportController extends Controller
{
    private const SLUGS = [
        'proveedores',
        'marcas',
        'pedidos-proveedores',
        'usuarios',
        'pedidos-clientes',
    ];

    public function download(Request $request, string $slug): Response|StreamedResponse
    {
        if (! in_array($slug, self::SLUGS, true)) {
            abort(404);
        }

        $format = strtolower((string) $request->query('format', 'csv'));
        if (! in_array($format, ['csv', 'pdf'], true)) {
            abort(400, 'Formato no válido. Use csv o pdf.');
        }

        return match ($slug) {
            'proveedores' => $format === 'pdf' ? $this->suppliersPdf() : $this->suppliersCsv(),
            'marcas' => $format === 'pdf' ? $this->brandsPdf() : $this->brandsCsv(),
            'pedidos-proveedores' => $format === 'pdf' ? $this->supplierOrdersPdf($request) : $this->supplierOrdersCsv($request),
            'usuarios' => $format === 'pdf' ? $this->clientsPdf() : $this->clientsCsv(),
            'pedidos-clientes' => $format === 'pdf' ? $this->clientOrdersPdf($request) : $this->clientOrdersCsv($request),
        };
    }

    private function resolvedLogoPath(): ?string
    {
        $path = public_path('assets/images/brand/logo-ciclo-finca-icon.png');

        return is_file($path) ? $path : null;
    }

    private function suppliersPdf(): Response
    {
        $rows = Supplier::query()->orderBy('name')->limit(AdminPdfExportLimits::REGISTRY_MAX_ROWS)->get();
        $headers = ['ID', 'Nombre', 'Contacto', 'Teléfono', 'Email', 'Dirección', 'Entrega (días)', 'Valoración', 'Estado'];
        $data = $rows->map(fn (Supplier $s): array => [
            (string) $s->supplier_id,
            $s->name,
            (string) ($s->primary_contact ?? ''),
            (string) ($s->phone ?? ''),
            (string) ($s->email ?? ''),
            Str::limit((string) ($s->address ?? ''), 80),
            (string) ($s->delivery_time ?? ''),
            (string) ($s->rating ?? ''),
            (string) ($s->status ?? ''),
        ])->values()->all();

        return $this->registryPdf(
            'Proveedores',
            'Listado de proveedores — Ciclo Finca 4',
            [],
            $headers,
            $data,
            'proveedores'
        );
    }

    private function suppliersCsv(): StreamedResponse
    {
        return $this->streamRegistryCsv(
            'proveedores_'.now()->format('Y-m-d_His').'.csv',
            ['ID', 'Nombre', 'Contacto', 'Teléfono', 'Email', 'Dirección', 'Entrega_dias', 'Valoracion', 'Estado'],
            function (callable $emitRow): void {
                foreach (Supplier::query()->orderBy('name')->cursor() as $s) {
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

    private function brandsPdf(): Response
    {
        $rows = Brand::query()->orderBy('name')->limit(AdminPdfExportLimits::REGISTRY_MAX_ROWS)->get();
        $headers = ['ID', 'Nombre'];
        $data = $rows->map(fn (Brand $b): array => [(string) $b->id, $b->name])->values()->all();

        return $this->registryPdf('Marcas', 'Catálogo de marcas — Ciclo Finca 4', [], $headers, $data, 'marcas');
    }

    private function brandsCsv(): StreamedResponse
    {
        return $this->streamRegistryCsv(
            'marcas_'.now()->format('Y-m-d_His').'.csv',
            ['ID', 'Nombre'],
            function (callable $emitRow): void {
                foreach (Brand::query()->orderBy('name')->cursor() as $b) {
                    if (! $b instanceof Brand) {
                        continue;
                    }
                    $emitRow([$b->id, $b->name]);
                }
            }
        );
    }

    /**
     * @return array<int, string>
     */
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

    private function supplierOrdersBase(Request $request): Builder
    {
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
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
        if ($request->filled('search')) {
            $search = trim((string) $request->get('search'));
            $query->where(function ($q) use ($search) {
                $q->where('num_order', 'like', '%'.$search.'%')
                    ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', '%'.$search.'%'));
            });
        }

        return $query;
    }

    private function supplierOrdersPdf(Request $request): Response
    {
        $base = $this->supplierOrdersBase($request);
        $total = (clone $base)->count();
        $filterLines = $this->supplierOrderFilterLines($request);
        $max = AdminPdfExportLimits::REGISTRY_MAX_ROWS;
        if ($total > $max) {
            $filterLines[] = 'Nota: el PDF incluye como máximo '.$max.' filas ('.$total.' pedidos coinciden).';
        }

        $rows = (clone $base)->limit($max)->get();
        $headers = ['Nº pedido', 'Proveedor', 'Fecha', 'Estado', 'Total', 'Productos (resumen)'];
        $data = [];
        foreach ($rows as $o) {
            if (! $o instanceof Order) {
                continue;
            }
            $supplierRel = $o->supplier;
            $supplierName = ($supplierRel instanceof Supplier) ? $supplierRel->name : '—';
            $dateStr = $o->date !== null ? $o->date->format('d/m/Y H:i') : '';
            $data[] = [
                (string) $o->num_order,
                $supplierName,
                $dateStr,
                (string) $o->state,
                '₡'.number_format((float) $o->total, 0, ',', '.'),
                Str::limit($this->summarizeSupplierOrderLines($o), 120),
            ];
        }

        return $this->registryPdf(
            'Pedidos a proveedores',
            'Pedidos de reposición — Ciclo Finca 4',
            $filterLines,
            $headers,
            $data,
            'pedidos-proveedores'
        );
    }

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
                        $supplierRel = $o->supplier;
                        $supplierName = ($supplierRel instanceof Supplier) ? $supplierRel->name : null;
                        $dateStr = $o->date !== null ? $o->date->format('Y-m-d H:i:s') : null;
                        $lines = $o->relationLoaded('orderItems')
                            ? $o->orderItems
                            : OrderItem::query()->where('order_num_order', $o->num_order)->get();
                        $payload = $lines->map(fn (OrderItem $line) => [
                            'product_id' => (int) $line->product_id,
                            'name' => $line->name,
                            'quantity' => (int) $line->quantity,
                            'unit_price' => (float) $line->unit_price,
                            'total' => (float) $line->total,
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

    private function clientsPdf(): Response
    {
        $rows = Client::query()->orderBy('name')->limit(AdminPdfExportLimits::REGISTRY_MAX_ROWS)->get();
        $headers = ['ID', 'Nombre', 'Apellido 1', 'Apellido 2', 'Email', 'Activo', 'Proveedor', 'Email verificado'];
        $data = $rows->map(fn (Client $c): array => [
            (string) $c->user_id,
            $c->name,
            (string) ($c->first_surname ?? ''),
            (string) ($c->second_surname ?? ''),
            $c->gmail,
            $c->active ? 'Sí' : 'No',
            (string) $c->provider,
            $c->email_verified ? 'Sí' : 'No',
        ])->values()->all();

        return $this->registryPdf(
            'Usuarios (clientes web)',
            'Cuentas registradas en la tienda — Ciclo Finca 4',
            [],
            $headers,
            $data,
            'usuarios-clientes'
        );
    }

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

    /**
     * @return array<int, string>
     */
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

    private function clientOrdersBase(Request $request): Builder
    {
        $query = Sale::query()
            ->where(function ($q) {
                $q->where('order_source', 'web_cart')
                    ->orWhereNull('order_source');
            })
            ->whereIn('status', ['pending', 'completed', 'cancelled', 'refunded'])
            ->notExpired()
            ->with(['client', 'saleItems.product']);

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

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

    private function clientOrdersPdf(Request $request): Response
    {
        $base = $this->clientOrdersBase($request);
        $total = (clone $base)->count();
        $filterLines = $this->clientOrderFilterLines($request);
        $max = AdminPdfExportLimits::REGISTRY_MAX_ROWS;
        if ($total > $max) {
            $filterLines[] = 'Nota: el PDF incluye como máximo '.$max.' filas ('.$total.' pedidos coinciden).';
        }

        $rows = (clone $base)->limit($max)->get();
        $headers = ['Factura / ID', 'Cliente', 'Fecha', 'Estado', 'Total', 'Ítems (resumen)'];
        $data = [];
        foreach ($rows as $sale) {
            if (! $sale instanceof Sale) {
                continue;
            }
            $customer = $sale->client
                ? trim($sale->client->name.' '.($sale->client->first_surname ?? '').' '.($sale->client->second_surname ?? ''))
                : ($sale->buyer_name ?: '—');
            $items = $sale->saleItems->map(function (SaleItem $item): string {
                $label = $item->product !== null ? $item->product->name : '?';

                return $label.' (×'.$item->quantity.')';
            })->implode(', ');
            $saleDate = $sale->sale_date;
            $data[] = [
                (string) ($sale->invoice_number ?? '#'.$sale->sale_id),
                Str::limit($customer, 40),
                $saleDate !== null ? $saleDate->format('d/m/Y H:i') : '',
                ucfirst((string) $sale->status),
                '₡'.number_format((float) $sale->total, 0, ',', '.'),
                Str::limit($items, 100),
            ];
        }

        return $this->registryPdf(
            'Pedidos clientes',
            'Pedidos web / carrito — Ciclo Finca 4',
            $filterLines,
            $headers,
            $data,
            'pedidos-clientes'
        );
    }

    private function clientOrdersCsv(Request $request): StreamedResponse
    {
        $chunk = AdminPdfExportLimits::REGISTRY_CSV_CHUNK;

        return $this->streamRegistryCsv(
            'pedidos_clientes_'.now()->format('Y-m-d_His').'.csv',
            [
                'sale_id', 'invoice', 'cliente', 'email_cliente', 'fecha', 'estado', 'total', 'items_resumen',
            ],
            function (callable $emitRow) use ($request, $chunk): void {
                $this->clientOrdersBase($request)->chunkById($chunk, function ($sales) use ($emitRow): void {
                    foreach ($sales as $sale) {
                        if (! $sale instanceof Sale) {
                            continue;
                        }
                        $customer = $sale->client
                            ? trim($sale->client->name.' '.($sale->client->first_surname ?? '').' '.($sale->client->second_surname ?? ''))
                            : ($sale->buyer_name ?: '');
                        $email = $sale->client ? $sale->client->gmail : ($sale->buyer_email ?? '');
                        $items = $sale->saleItems->map(function (SaleItem $item): string {
                            $label = $item->product !== null ? $item->product->name : '?';

                            return $label.' (x'.$item->quantity.')';
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

    /**
     * @param  array<int, string>  $filterLines
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, string>>  $rows
     */
    private function registryPdf(
        string $title,
        string $subtitle,
        array $filterLines,
        array $headers,
        array $rows,
        string $filenameSlug
    ): Response {
        $pdf = PDF::loadView('admin.exports.registry-table-pdf', [
            'pdfTitle' => $title,
            'pdfSubtitle' => $subtitle,
            'logoPath' => $this->resolvedLogoPath(),
            'filterLines' => $filterLines,
            'generatedFor' => 'Administración',
            'headers' => $headers,
            'rows' => $rows,
        ]);

        return $pdf->download(ReportPdfFilename::make($filenameSlug));
    }

    /**
     * CSV en streaming: una fila a la vez sin acumular todo el dataset en memoria.
     *
     * @param  callable(callable(array<int, mixed>): void): void  $producer  Recibe $emitRow para cada fila.
     */
    private function streamRegistryCsv(string $filename, array $headerRow, callable $producer): StreamedResponse
    {
        $httpHeaders = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($headerRow, $producer): void {
            $file = fopen('php://output', 'w');
            if ($file === false) {
                return;
            }
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, $headerRow, ';');
            $emitRow = static function (array $row) use ($file): void {
                fputcsv($file, array_map(fn ($v) => $v === null ? '' : $v, $row), ';');
            };
            $producer($emitRow);
            fclose($file);
        };

        return response()->stream($callback, 200, $httpHeaders);
    }
}
