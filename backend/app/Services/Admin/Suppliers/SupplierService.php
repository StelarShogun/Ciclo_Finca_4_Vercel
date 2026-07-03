<?php

namespace App\Services\Admin\Suppliers;

use App\Http\Resources\Admin\SupplierResource;
use App\Models\Supplier;
use App\Services\Client\Inertia\ListPaginationPayload;
use App\Services\Shared\Security\SensitiveDataMasker;
use App\Support\AdminDashboardCache;
use App\Support\AdminPerPage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

final class SupplierService
{
    /**
     * @param  array<string,mixed>  $filters
     * @return array<string,mixed>
     */
    public function indexPayload(array $filters): array
    {
        $name = mb_substr(trim((string) ($filters['name'] ?? '')), 0, 100);
        $contact = mb_substr(trim((string) ($filters['contact'] ?? '')), 0, 100);

        $query = Supplier::query()
            ->when($name !== '', fn (Builder $query) => $query->where('name', 'like', '%'.$name.'%'))
            ->when($contact !== '', fn (Builder $query) => $query->where('primary_contact', 'like', '%'.$contact.'%'));

        $averageRating = $query->avg('rating');
        $suppliers = $query
            ->paginate(AdminPerPage::resolve($filters['per_page'] ?? 10))
            ->withQueryString();

        return [
            'suppliers' => SupplierResource::collection($suppliers->getCollection())->resolve(),
            'averageRating' => (float) ($averageRating ?? 0),
            'pagination' => ListPaginationPayload::from($suppliers),
            'filters' => [
                'name' => $name,
                'contact' => $contact,
            ],
        ];
    }

    /**
     * @param  array<string,mixed>  $data
     */
    public function create(array $data): Supplier
    {
        $supplier = Supplier::query()->create($data);
        AdminDashboardCache::forget();

        return $supplier;
    }

    /**
     * @param  array<string,mixed>  $data
     */
    public function update(Supplier $supplier, array $data): Supplier
    {
        $supplier->update($data);
        AdminDashboardCache::forget();

        return $supplier->refresh();
    }

    public function delete(Supplier $supplier): void
    {
        $supplier->delete();
        AdminDashboardCache::forget();
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonPayload(Supplier $supplier): array
    {
        return [
            'success' => true,
            'data' => (new SupplierResource($supplier))->resolve(),
        ];
    }

    public function logFailure(string $event, \Throwable $exception, ?Supplier $supplier = null): void
    {
        Log::error($event, SensitiveDataMasker::exceptionContext($exception, [
            'supplier_id' => $supplier?->supplier_id,
            'admin_id' => auth('admin')->id(),
        ]));
    }
}
