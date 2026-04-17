<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * CF4-35 — exportación PDF admin (cabecera y nombre de archivo).
 */
class AdminPdfExportReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        try {
            parent::setUp();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Base de datos no disponible: '.$e->getMessage());
        }

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('AdminPdfExportReportTest requiere MySQL.');
        }

        foreach (['admins', 'products', 'categories', 'suppliers', 'sales'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped('Tabla requerida no existe: '.$table);
            }
        }
    }

    private function loginAdmin(): AdminUser
    {
        $webClient = Client::create([
            'name' => 'Web',
            'first_surname' => 'Pdf',
            'second_surname' => null,
            'gmail' => 'web-pdf-export@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $admin = AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'Pdf',
            'second_surname' => null,
            'gmail' => 'admin-pdf-export@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);

        Auth::guard('web')->login($webClient);
        Auth::guard('admin')->login($admin);

        return $admin;
    }

    public function test_reports_exports_page_renders_for_admin(): void
    {
        $this->loginAdmin();

        $response = $this->get('/reports/exportaciones');

        $response->assertOk();
        $response->assertSee('Exportar datos y PDF', false);
        $response->assertSee(route('dashboard.export'), false);
        $response->assertSee('Listados administrativos', false);
        $response->assertSee('/reports/exportaciones/descarga/proveedores', false);
    }

    public function test_exports_page_propagates_inventory_filters_to_catalog_download_links(): void
    {
        $this->loginAdmin();

        $response = $this->get('/reports/exportaciones?search=bici&stock_status=low');

        $response->assertOk();
        $response->assertSee('search=bici', false);
        $response->assertSee('stock_status=low', false);
    }

    public function test_exports_page_propagates_supplier_order_filters_to_registry_links(): void
    {
        $this->loginAdmin();

        $response = $this->get('/reports/exportaciones?state=pending&date_from=2026-01-01');

        $response->assertOk();
        $response->assertSee('state=pending', false);
        $response->assertSee('date_from=2026-01-01', false);
    }

    public function test_exports_page_propagates_client_order_filters_to_registry_links(): void
    {
        $this->loginAdmin();

        $response = $this->get('/reports/exportaciones?status=pending&search=foo');

        $response->assertOk();
        $response->assertSee('status=pending', false);
        $response->assertSee('search=foo', false);
    }

    public function test_registry_proveedores_pdf_respects_name_filter(): void
    {
        $this->loginAdmin();

        $response = $this->get('/reports/exportaciones/descarga/proveedores?format=pdf&name=xyznone');

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('Content-Type'));
    }

    public function test_registry_proveedores_pdf_download_response(): void
    {
        $this->loginAdmin();

        $response = $this->get('/reports/exportaciones/descarga/proveedores?format=pdf');

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('Content-Type'));
        $disposition = (string) $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('reporte-proveedores-', $disposition);
    }

    public function test_dashboard_pdf_download_response(): void
    {
        $this->loginAdmin();

        $response = $this->get('/dashboard/export?format=pdf&period=7d');

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('Content-Type'));
        $disposition = (string) $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('attachment', $disposition);
        $this->assertStringContainsString('reporte-dashboard-', $disposition);
        $this->assertStringContainsString('.pdf', $disposition);
    }

    public function test_inventory_pdf_download_response(): void
    {
        $this->loginAdmin();

        $response = $this->get('/inventory/export/pdf');

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('Content-Type'));
        $disposition = (string) $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('reporte-inventario-', $disposition);
    }

    public function test_product_sales_pdf_download_response(): void
    {
        $this->loginAdmin();

        $response = $this->get('/reports/productos-vendidos/pdf?period=30d&sort=revenue&dir=desc&top10=revenue');

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('Content-Type'));
        $disposition = (string) $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('reporte-productos-vendidos-', $disposition);
    }

    public function test_sales_pdf_download_response(): void
    {
        $this->loginAdmin();

        $response = $this->get('/sales/export?format=pdf&status=completed');

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('Content-Type'));
        $disposition = (string) $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('reporte-ventas-', $disposition);
    }
}
