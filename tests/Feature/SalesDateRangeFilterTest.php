<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SalesDateRangeFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        try {
            parent::setUp();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Base de datos no disponible: ' . $e->getMessage());
        }
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('SalesDateRangeFilterTest requiere MySQL.');
        }
        if (! Schema::hasTable('sales')) {
            $this->markTestSkipped('Falta la tabla requerida (sales).');
        }
    }

    private function createAdmin(): AdminUser
    {
        return AdminUser::create([
            'name'           => 'Admin',
            'first_surname'  => 'DateFilter',
            'second_surname' => null,
            'gmail'          => 'admin-date-filter@example.com',
            'password'       => bcrypt('password'),
            'last_access'    => now(),
        ]);
    }

    /**
     * Crea una venta mínima con la fecha indicada y el estado dado.
     * La fecha se almacena directamente en UTC para evitar conversiones del accessor.
     */
    private function createSale(string $saleDate, string $status = 'completed'): Sale
    {
        static $seq = 0;
        $seq++;

        return Sale::create([
            'invoice_number'    => 'CF4-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
            'client_id'         => null,
            'seller_admin_id'   => null,
            'subtotal'          => 1000.00,
            'iva'               => 130.00,
            'discount'          => 0.00,
            'total'             => 1130.00,
            'payment_method'    => 'cash',
            'payment_reference' => null,
            'status'            => $status,
            'notes'             => null,
            'sale_date'         => $saleDate,
            'buyer_name'        => 'Cliente Test',
            'buyer_email'       => null,
            'order_source'      => 'walk_in',
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // CA-02 – Resultados limitados al rango seleccionado
    // ─────────────────────────────────────────────────────────────

    /** CA-02-01: solo aparecen ventas dentro del rango; las anteriores quedan excluidas. */
    public function test_ca02_01_sales_before_start_date_are_excluded(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-15 12:00:00', 'UTC'));

        $admin = $this->createAdmin();

        $this->createSale('2026-04-09 10:00:00'); // fuera – día anterior al rango
        $inside = $this->createSale('2026-04-10 08:00:00'); // dentro – primer día del rango

        $resp = $this->actingAs($admin, 'admin')
            ->get(route('sales.index', [
                'start_date' => '2026-04-10',
                'end_date'   => '2026-04-15',
            ]));

        $resp->assertOk();
        $resp->assertViewHas('sales', function ($paginator) use ($inside) {
            $ids = $paginator->pluck('sale_id');
            return $ids->contains($inside->sale_id)
                && $ids->count() === 1;
        });
    }

    /** CA-02-02: solo aparecen ventas dentro del rango; las posteriores quedan excluidas. */
    public function test_ca02_02_sales_after_end_date_are_excluded(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-20 12:00:00', 'UTC'));

        $admin = $this->createAdmin();

        $inside  = $this->createSale('2026-04-15 23:59:59'); // dentro – último día del rango
        $outside = $this->createSale('2026-04-16 00:00:01'); // fuera – día posterior al rango

        $resp = $this->actingAs($admin, 'admin')
            ->get(route('sales.index', [
                'start_date' => '2026-04-10',
                'end_date'   => '2026-04-15',
            ]));

        $resp->assertOk();
        $resp->assertViewHas('sales', function ($paginator) use ($inside, $outside) {
            $ids = $paginator->pluck('sale_id');
            return $ids->contains($inside->sale_id)
                && ! $ids->contains($outside->sale_id);
        });
    }

    /** CA-02-03: un rango de un solo día devuelve únicamente las ventas de ese día. */
    public function test_ca02_03_single_day_range_returns_only_that_days_sales(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-15 12:00:00', 'UTC'));

        $admin = $this->createAdmin();

        $before  = $this->createSale('2026-04-13 23:59:59');
        $target  = $this->createSale('2026-04-14 11:30:00');
        $after   = $this->createSale('2026-04-15 00:00:01');

        $resp = $this->actingAs($admin, 'admin')
            ->get(route('sales.index', [
                'start_date' => '2026-04-14',
                'end_date'   => '2026-04-14',
            ]));

        $resp->assertOk();
        $resp->assertViewHas('sales', function ($paginator) use ($target, $before, $after) {
            $ids = $paginator->pluck('sale_id');
            return $ids->contains($target->sale_id)
                && ! $ids->contains($before->sale_id)
                && ! $ids->contains($after->sale_id);
        });
    }

    // ─────────────────────────────────────────────────────────────
    // CA-03 – Cobertura del día completo en ambas fechas
    // ─────────────────────────────────────────────────────────────

    /** CA-03-01: una venta registrada a las 00:00:00 de la fecha inicial es incluida. */
    public function test_ca03_01_sale_at_start_of_start_date_is_included(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-15 12:00:00', 'UTC'));

        $admin = $this->createAdmin();
        $first = $this->createSale('2026-04-10 00:00:00'); // primera hora del día inicial

        $resp = $this->actingAs($admin, 'admin')
            ->get(route('sales.index', [
                'start_date' => '2026-04-10',
                'end_date'   => '2026-04-15',
            ]));

        $resp->assertOk();
        $resp->assertViewHas('sales', function ($paginator) use ($first) {
            return $paginator->pluck('sale_id')->contains($first->sale_id);
        });
    }

    /** CA-03-02: una venta registrada a las 23:59:59 de la fecha final es incluida. */
    public function test_ca03_02_sale_at_end_of_end_date_is_included(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-16 12:00:00', 'UTC'));

        $admin = $this->createAdmin();
        $last  = $this->createSale('2026-04-15 23:59:59'); // última hora del día final

        $resp = $this->actingAs($admin, 'admin')
            ->get(route('sales.index', [
                'start_date' => '2026-04-10',
                'end_date'   => '2026-04-15',
            ]));

        $resp->assertOk();
        $resp->assertViewHas('sales', function ($paginator) use ($last) {
            return $paginator->pluck('sale_id')->contains($last->sale_id);
        });
    }

    // ─────────────────────────────────────────────────────────────
    // CA-04 – Validación de rango inválido (fecha inicial > fecha final)
    // Solo aplica al endpoint byCategory, que ejecuta validación explícita
    // con la regla after_or_equal. El listado principal (index) usa whereDate
    // y no valida la inversión de rango; ese comportamiento está documentado aquí.
    // ─────────────────────────────────────────────────────────────

    /** CA-04-01: en byCategory, una fecha inicial mayor que la final produce error de validación. */
    public function test_ca04_01_by_category_rejects_start_date_greater_than_end_date(): void
    {
        $admin = $this->createAdmin();

        $resp = $this->actingAs($admin, 'admin')
            ->get(route('admin.sales.by-category', [
                'date_range' => 'custom',
                'date_from'  => '2026-04-15',
                'date_to'    => '2026-04-10', // anterior a date_from
            ]));

        $resp->assertSessionHasErrors(['date_to']);
    }

    /** CA-04-02: en byCategory, ambas fechas iguales son aceptadas (after_or_equal). */
    public function test_ca04_02_by_category_accepts_equal_start_and_end_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-15 12:00:00', 'UTC'));

        $admin = $this->createAdmin();

        $resp = $this->actingAs($admin, 'admin')
            ->get(route('admin.sales.by-category', [
                'date_range' => 'custom',
                'date_from'  => '2026-04-14',
                'date_to'    => '2026-04-14',
            ]));

        $resp->assertOk();
        $resp->assertSessionHasNoErrors();
    }

    // ─────────────────────────────────────────────────────────────
    // CA-05 – Mensaje informativo cuando no hay resultados
    // ─────────────────────────────────────────────────────────────

    /** CA-05-01: cuando el rango no contiene ventas la vista se renderiza sin errores. */
    public function test_ca05_01_empty_range_renders_view_without_errors(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-20 12:00:00', 'UTC'));

        $admin = $this->createAdmin();

        // No se crean ventas; se espera colección vacía pero respuesta 200
        $resp = $this->actingAs($admin, 'admin')
            ->get(route('sales.index', [
                'start_date' => '2026-04-01',
                'end_date'   => '2026-04-05',
            ]));

        $resp->assertOk();
        $resp->assertViewHas('sales', function ($paginator) {
            return $paginator->isEmpty();
        });
    }

    /** CA-05-02: la vista contiene el texto de lista vacía cuando no hay resultados en el rango. */
    public function test_ca05_02_empty_range_shows_no_sales_message_in_view(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-20 12:00:00', 'UTC'));

        $admin = $this->createAdmin();

        $resp = $this->actingAs($admin, 'admin')
            ->get(route('sales.index', [
                'start_date' => '2026-04-01',
                'end_date'   => '2026-04-05',
            ]));

        $resp->assertOk();
        $resp->assertSee('No hay ventas registradas');
    }

    // ─────────────────────────────────────────────────────────────
    // CA-06 – Persistencia de los valores seleccionados
    // ─────────────────────────────────────────────────────────────

    /** CA-06-01: los valores de start_date y end_date están presentes en la respuesta tras aplicar el filtro. */
    public function test_ca06_01_selected_dates_are_present_in_response_after_filter(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-15 12:00:00', 'UTC'));

        $admin = $this->createAdmin();
        $this->createSale('2026-04-12 10:00:00');

        $resp = $this->actingAs($admin, 'admin')
            ->get(route('sales.index', [
                'start_date' => '2026-04-10',
                'end_date'   => '2026-04-15',
            ]));

        $resp->assertOk();

        // Los campos del formulario deben repoblarse con los valores enviados
        $resp->assertSee('2026-04-10');
        $resp->assertSee('2026-04-15');
    }

    // ─────────────────────────────────────────────────────────────
    // CA-07 – Compatibilidad con otros filtros del módulo
    // ─────────────────────────────────────────────────────────────

    /** CA-07-01: el filtro de fechas combinado con payment_method devuelve solo las ventas que cumplen ambos criterios. */
    public function test_ca07_01_date_range_combined_with_payment_method_filters_correctly(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-20 12:00:00', 'UTC'));

        $admin = $this->createAdmin();

        $cashInRange   = $this->createSaleWithPayment('2026-04-12 10:00:00', 'cash');
        $sinpeInRange  = $this->createSaleWithPayment('2026-04-12 11:00:00', 'sinpe');
        $cashOutRange  = $this->createSaleWithPayment('2026-04-20 10:00:00', 'cash');

        $resp = $this->actingAs($admin, 'admin')
            ->get(route('sales.index', [
                'start_date'     => '2026-04-10',
                'end_date'       => '2026-04-15',
                'payment_method' => 'cash',
            ]));

        $resp->assertOk();
        $resp->assertViewHas('sales', function ($paginator) use ($cashInRange, $sinpeInRange, $cashOutRange) {
            $ids = $paginator->pluck('sale_id');
            return $ids->contains($cashInRange->sale_id)
                && ! $ids->contains($sinpeInRange->sale_id)
                && ! $ids->contains($cashOutRange->sale_id);
        });
    }

    /** CA-07-02: el filtro de fechas no altera el comportamiento del filtro de estado. */
    public function test_ca07_02_date_range_does_not_override_status_filter(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-20 12:00:00', 'UTC'));

        $admin = $this->createAdmin();

        $completed  = $this->createSale('2026-04-12 10:00:00', 'completed');
        $cancelled  = $this->createSale('2026-04-12 11:00:00', 'cancelled');

        $resp = $this->actingAs($admin, 'admin')
            ->get(route('sales.index', [
                'start_date' => '2026-04-10',
                'end_date'   => '2026-04-15',
                'status'     => 'cancelled',
            ]));

        $resp->assertOk();
        $resp->assertViewHas('sales', function ($paginator) use ($completed, $cancelled) {
            $ids = $paginator->pluck('sale_id');
            return $ids->contains($cancelled->sale_id)
                && ! $ids->contains($completed->sale_id);
        });
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers internos
    // ─────────────────────────────────────────────────────────────

    /** Crea una venta con un método de pago específico. */
    private function createSaleWithPayment(string $saleDate, string $paymentMethod): Sale
    {
        static $seq = 100;
        $seq++;

        return Sale::create([
            'invoice_number'    => 'CF4-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
            'client_id'         => null,
            'seller_admin_id'   => null,
            'subtotal'          => 1000.00,
            'iva'               => 130.00,
            'discount'          => 0.00,
            'total'             => 1130.00,
            'payment_method'    => $paymentMethod,
            'payment_reference' => null,
            'status'            => 'completed',
            'notes'             => null,
            'sale_date'         => $saleDate,
            'buyer_name'        => 'Cliente Test',
            'buyer_email'       => null,
            'order_source'      => 'walk_in',
        ]);
    }
}
