<?php

namespace Tests\Unit;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Tests\TestCase;

class SuppliersIndexFlashAlertsTest extends TestCase
{
    public function test_suppliers_index_renders_success_flash_alert(): void
    {
        $this->withSession(['status' => 'Supplier deleted successfully.']);

        $suppliers = new LengthAwarePaginator(
            new Collection([]),
            0,
            10,
            1,
            ['path' => '/suppliers']
        );

        $html = view('admin.suppliers.index', [
            'suppliers' => $suppliers,
            'averageRating' => 0,
        ])->render();

        $this->assertStringContainsString('alert-success', $html);
        $this->assertStringContainsString('Supplier deleted successfully.', $html);
    }

    public function test_suppliers_index_renders_error_flash_alert(): void
    {
        $this->withSession(['error' => 'Supplier not found.']);

        $suppliers = new LengthAwarePaginator(
            new Collection([]),
            0,
            10,
            1,
            ['path' => '/suppliers']
        );

        $html = view('admin.suppliers.index', [
            'suppliers' => $suppliers,
            'averageRating' => 0,
        ])->render();

        $this->assertStringContainsString('alert-danger', $html);
        $this->assertStringContainsString('Supplier not found.', $html);
    }
}
