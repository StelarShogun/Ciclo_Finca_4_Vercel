<?php

namespace Tests\Unit;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Tests\TestCase;

class SuppliersIndexFlashAlertsTest extends TestCase
{
    public function test_suppliers_index_renders_success_flash_for_sweetalert(): void
    {
        $this->withSession(['status' => 'Supplier deleted successfully.']);

        $html = $this->renderSuppliersIndex();

        $this->assertStringContainsString('window.__cf4Flash', $html);
        $this->assertStringContainsString('Supplier deleted successfully.', $html);
        $this->assertStringNotContainsString('alert-success', $html);
    }

    public function test_suppliers_index_renders_error_flash_for_sweetalert(): void
    {
        $this->withSession(['error' => 'Supplier not found.']);

        $html = $this->renderSuppliersIndex();

        $this->assertStringContainsString('window.__cf4Flash', $html);
        $this->assertStringContainsString('Supplier not found.', $html);
        $this->assertStringNotContainsString('alert-danger', $html);
    }

    private function renderSuppliersIndex(): string
    {
        $suppliers = new LengthAwarePaginator(
            new Collection([]),
            0,
            10,
            1,
            ['path' => '/suppliers']
        );

        return view('admin.suppliers.index', [
            'suppliers' => $suppliers,
            'averageRating' => 0,
        ])->render();
    }
}
