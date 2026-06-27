<?php

namespace Tests\Feature\Admin;

use App\Models\AdminUser;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon as SupportCarbon;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Cubre la serie de ventas configurable por rango del dashboard
 * (DashboardDataService::resolveSalesRange / salesSeries y su validación).
 */
class DashboardSalesRangeTest extends TestCase
{
    use RefreshDatabase;

    private AdminUser $admin;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.timezone' => 'America/Costa_Rica']);
        Carbon::setTestNow(Carbon::parse('2026-05-22 15:00:00', 'America/Costa_Rica'));
        SupportCarbon::setTestNow(Carbon::parse('2026-05-22 15:00:00', 'America/Costa_Rica'));
        Cache::flush();
        $this->admin = AdminUser::firstOrCreate(
            ['gmail' => 'admin-salesrange@example.com'],
            [
                'name' => 'Admin',
                'first_surname' => 'Range',
                'second_surname' => null,
                'password' => bcrypt('password'),
                'last_access' => now(),
            ]
        );
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        SupportCarbon::setTestNow();
        parent::tearDown();
    }

    private function dashboard(array $query = [])
    {
        return $this->actingAs($this->admin, 'admin')
            ->get(route('dashboard', $query));
    }

    public function test_default_range_is_last7_with_seven_day_series(): void
    {
        $this->dashboard()->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Dashboard/Index', false)
            ->where('salesRange', 'last7')
            ->has('salesByDay', 7)
        );
    }

    public function test_named_ranges_produce_expected_series_length(): void
    {
        $this->dashboard(['range' => 'last15'])->assertInertia(fn (Assert $page) => $page
            ->where('salesRange', 'last15')->has('salesByDay', 15));

        $this->dashboard(['range' => 'last30'])->assertInertia(fn (Assert $page) => $page
            ->where('salesRange', 'last30')->has('salesByDay', 30));
    }

    public function test_custom_range_echoes_bounds(): void
    {
        $this->dashboard(['range' => 'custom', 'from' => '2026-05-10', 'to' => '2026-05-12'])
            ->assertInertia(fn (Assert $page) => $page
                ->where('salesRange', 'custom')
                ->where('salesFrom', '2026-05-10')
                ->where('salesTo', '2026-05-12')
                ->has('salesByDay', 3)
            );
    }

    public function test_inverted_custom_range_is_rejected_by_validation(): void
    {
        // 'after_or_equal:from' rechaza un rango invertido antes de llegar al servicio;
        // los inputs <input type="date"> con min/max ya lo evitan en el front.
        $this->actingAs($this->admin, 'admin')
            ->get(route('dashboard', ['range' => 'custom', 'from' => '2026-05-12', 'to' => '2026-05-10']))
            ->assertSessionHasErrors('to');
    }

    public function test_custom_range_is_clamped_to_92_days(): void
    {
        // Más de 92 días debe recortarse a 93 puntos (92 días + extremo inclusivo).
        $this->dashboard(['range' => 'custom', 'from' => '2025-01-01', 'to' => '2025-12-31'])
            ->assertInertia(fn (Assert $page) => $page
                ->where('salesRange', 'custom')
                ->where('salesTo', '2025-12-31')
                ->where('salesFrom', '2025-09-30')
                ->has('salesByDay', 93)
            );
    }

    public function test_relative_date_strings_are_rejected_by_validation(): void
    {
        // 'date_format:Y-m-d' bloquea cadenas relativas que strtotime aceptaría.
        $this->actingAs($this->admin, 'admin')
            ->get(route('dashboard', ['range' => 'custom', 'from' => 'tomorrow', 'to' => '2026-05-10']))
            ->assertSessionHasErrors('from');
    }
}
