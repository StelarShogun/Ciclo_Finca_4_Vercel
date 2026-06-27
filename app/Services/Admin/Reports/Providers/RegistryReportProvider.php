<?php

namespace App\Services\Admin\Reports\Providers;

use App\DTOs\Admin\Reports\RegistryReportData;
use App\Exceptions\ReportExportException;
use App\Models\Brand;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;
use App\Services\Admin\AdminPdfExportLimits;
use App\Services\Admin\Reports\Contracts\ReportDataProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class RegistryReportProvider implements ReportDataProvider
{
    public function forSlug(string $slug, Request $request): RegistryReportData
    {
        return match ($slug) {
            'proveedores' => $this->suppliers($request),
            'marcas' => $this->brands($request),
            'pedidos-proveedores' => $this->supplierOrders($request),
            'usuarios' => $this->clients(),
            'pedidos-clientes' => $this->clientOrders($request),
            default => throw ReportExportException::unknownReport(),
        };
    }

    private function suppliers(Request $request): RegistryReportData
    {
        $base = Supplier::query()->orderBy('name');

        if ($request->filled('name')) {
            $base->where('name', 'like', '%'.trim((string) $request->get('name')).'%');
        }

        if ($request->filled('contact')) {
            $base->where('primary_contact', 'like', '%'.trim((string) $request->get('contact')).'%');
        }

        return new RegistryReportData(
            title: 'Proveedores',
            subtitle: 'Listado de proveedores — Ciclo Finca 4',
            filenameSlug: 'proveedores',
            filterLines: $this->withLimitNotice($this->filterLines($request, [
                'name' => 'Nombre',
                'contact' => 'Contacto',
            ]), (clone $base)->count(), 'proveedores'),
            headers: ['ID', 'Nombre', 'Contacto', 'Teléfono', 'Email', 'Dirección', 'Entrega (días)', 'Valoración', 'Estado'],
            rows: (clone $base)->limit(AdminPdfExportLimits::REGISTRY_MAX_ROWS)->get()->map(fn (Supplier $supplier): array => [
                (string) $supplier->supplier_id,
                (string) $supplier->name,
                (string) ($supplier->primary_contact ?? ''),
                (string) ($supplier->phone ?? ''),
                (string) ($supplier->email ?? ''),
                (string) ($supplier->address ?? ''),
                (string) ($supplier->delivery_time ?? ''),
                (string) ($supplier->rating ?? ''),
                (string) ($supplier->status ?? ''),
            ])->values()->all(),
        );
    }

    private function brands(Request $request): RegistryReportData
    {
        $base = Brand::query()->orderBy('name');

        if ($request->filled('name')) {
            $base->where('name', 'like', '%'.trim((string) $request->get('name')).'%');
        }

        return new RegistryReportData(
            title: 'Marcas',
            subtitle: 'Catálogo de marcas — Ciclo Finca 4',
            filenameSlug: 'marcas',
            filterLines: $this->withLimitNotice($this->filterLines($request, ['name' => 'Nombre']), (clone $base)->count(), 'marcas'),
            headers: ['ID', 'Nombre'],
            rows: (clone $base)->limit(AdminPdfExportLimits::REGISTRY_MAX_ROWS)->get()->map(fn (Brand $brand): array => [
                (string) $brand->id,
                (string) $brand->name,
            ])->values()->all(),
        );
    }

    private function supplierOrders(Request $request): RegistryReportData
    {
        $base = $this->supplierOrdersBase($request);

        return new RegistryReportData(
            title: 'Pedidos a proveedores',
            subtitle: 'Pedidos de reposición — Ciclo Finca 4',
            filenameSlug: 'pedidos-proveedores',
            filterLines: $this->withLimitNotice($this->filterLines($request, [
                'state' => 'Estado',
                'date_from' => 'Desde',
                'date_to' => 'Hasta',
                'search' => 'Búsqueda',
            ]), (clone $base)->count(), 'pedidos'),
            headers: ['Nº pedido', 'Proveedor', 'Fecha', 'Estado', 'Total', 'Productos (resumen)'],
            rows: (clone $base)->limit(AdminPdfExportLimits::REGISTRY_MAX_ROWS)->get()->map(fn (Order $order): array => [
                (string) $order->num_order,
                $order->supplier instanceof Supplier ? (string) $order->supplier->name : '—',
                $order->date?->format('d/m/Y H:i') ?? '',
                (string) $order->state,
                '₡'.number_format((float) $order->total, 0, ',', '.'),
                Str::limit($this->summarizeSupplierOrderLines($order), 120),
            ])->values()->all(),
        );
    }

    private function clients(): RegistryReportData
    {
        return new RegistryReportData(
            title: 'Usuarios (clientes web)',
            subtitle: 'Cuentas registradas en la tienda — Ciclo Finca 4',
            filenameSlug: 'usuarios-clientes',
            filterLines: [],
            headers: ['ID', 'Nombre', 'Apellido 1', 'Apellido 2', 'Email', 'Activo', 'Proveedor', 'Email verificado'],
            rows: Client::query()
                ->orderBy('name')
                ->limit(AdminPdfExportLimits::REGISTRY_MAX_ROWS)
                ->get()
                ->map(fn (Client $client): array => [
                    (string) $client->user_id,
                    (string) $client->name,
                    (string) ($client->first_surname ?? ''),
                    (string) ($client->second_surname ?? ''),
                    (string) $client->gmail,
                    $client->active ? 'Sí' : 'No',
                    (string) $client->provider,
                    $client->email_verified ? 'Sí' : 'No',
                ])
                ->values()
                ->all(),
        );
    }

    private function clientOrders(Request $request): RegistryReportData
    {
        $base = $this->clientOrdersBase($request);

        return new RegistryReportData(
            title: 'Pedidos clientes',
            subtitle: 'Pedidos web / carrito — Ciclo Finca 4',
            filenameSlug: 'pedidos-clientes',
            filterLines: $this->withLimitNotice($this->filterLines($request, [
                'status' => 'Estado',
                'search' => 'Búsqueda',
            ]), (clone $base)->count(), 'pedidos'),
            headers: ['Factura / ID', 'Cliente', 'Fecha', 'Estado', 'Total', 'Ítems (resumen)'],
            rows: (clone $base)->limit(AdminPdfExportLimits::REGISTRY_MAX_ROWS)->get()->map(fn (Sale $sale): array => [
                (string) ($sale->invoice_number ?? '#'.$sale->sale_id),
                Str::limit($this->customerName($sale), 40),
                $sale->sale_date?->format('d/m/Y H:i') ?? '',
                ucfirst((string) $sale->status),
                '₡'.number_format((float) $sale->total, 0, ',', '.'),
                Str::limit($this->summarizeSaleItems($sale), 100),
            ])->values()->all(),
        );
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
            $query->where(function ($inner) use ($search) {
                $inner->where('num_order', 'like', '%'.$search.'%')
                    ->orWhereHas('supplier', fn ($supplier) => $supplier->where('name', 'like', '%'.$search.'%'));
            });
        }

        return $query;
    }

    private function clientOrdersBase(Request $request): Builder
    {
        $query = Sale::query()
            ->where(function ($inner) {
                $inner->where('order_source', 'web_cart')
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
            $query->where(function ($inner) use ($search) {
                $inner->where('sale_id', 'like', '%'.$search.'%')
                    ->orWhere('invoice_number', 'like', '%'.$search.'%')
                    ->orWhereHas('client', function ($client) use ($search) {
                        $client->where('name', 'like', '%'.$search.'%')
                            ->orWhere('first_surname', 'like', '%'.$search.'%')
                            ->orWhere('gmail', 'like', '%'.$search.'%');
                    });
            });
        }

        return $query->orderBy('sale_date', 'desc');
    }

    /** @param  array<string, string>  $labels */
    private function filterLines(Request $request, array $labels): array
    {
        $lines = [];

        foreach ($labels as $key => $label) {
            if ($request->filled($key)) {
                $lines[] = $label.': '.$request->string($key);
            }
        }

        return $lines;
    }

    /** @param  array<int, string>  $lines */
    private function withLimitNotice(array $lines, int $total, string $label): array
    {
        $max = AdminPdfExportLimits::REGISTRY_MAX_ROWS;

        if ($total > $max) {
            $lines[] = 'Nota: la exportación incluye como máximo '.$max.' filas ('.$total.' '.$label.' coinciden).';
        }

        return $lines;
    }

    private function summarizeSupplierOrderLines(Order $order): string
    {
        $order->loadMissing('orderItems');

        return $order->orderItems
            ->map(fn (OrderItem $line): string => $line->name.' × '.$line->quantity)
            ->implode(', ');
    }

    private function customerName(Sale $sale): string
    {
        if ($sale->client) {
            return trim($sale->client->name.' '.($sale->client->first_surname ?? '').' '.($sale->client->second_surname ?? ''));
        }

        return $sale->buyer_name ?: '—';
    }

    private function summarizeSaleItems(Sale $sale): string
    {
        return $sale->saleItems
            ->map(fn (SaleItem $item): string => ($item->product !== null ? $item->product->name : '?').' (×'.$item->quantity.')')
            ->implode(', ');
    }
}
