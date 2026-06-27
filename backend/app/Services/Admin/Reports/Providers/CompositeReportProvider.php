<?php

namespace App\Services\Admin\Reports\Providers;

use App\DTOs\Admin\Reports\RegistryReportData;
use App\Services\Admin\Reports\Contracts\ReportDataProvider;
use Illuminate\Http\Request;

final readonly class CompositeReportProvider implements ReportDataProvider
{
    /** @var array<string, ReportDataProvider> */
    private array $providers;

    public function __construct(
        SuppliersReportProvider $suppliers,
        BrandsReportProvider $brands,
        SupplierOrdersReportProvider $supplierOrders,
        ClientsReportProvider $clients,
        SalesReportProvider $sales,
    ) {
        $this->providers = [
            $suppliers->slug() => $suppliers,
            $brands->slug() => $brands,
            $supplierOrders->slug() => $supplierOrders,
            $clients->slug() => $clients,
            $sales->slug() => $sales,
        ];
    }

    public function forSlug(string $slug, Request $request): RegistryReportData
    {
        $provider = $this->providers[$slug] ?? null;
        abort_unless($provider instanceof ReportDataProvider, 404);

        return $provider->forSlug($slug, $request);
    }
}
