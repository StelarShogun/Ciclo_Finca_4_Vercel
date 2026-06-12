<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CF4116SalesDateRangeFilterTest extends TestCase
{
    use RefreshDatabase;

    private function getAdmin(): AdminUser
    {
        return AdminUser::firstOrCreate(
            ['gmail' => 'admin-cf4116@example.com'],
            [
                'name' => 'Admin',
                'first_surname' => 'CF4116',
                'second_surname' => null,
                'password' => bcrypt('password'),
                'last_access' => now(),
            ]
        );
    }

    /** Applies date range filter and returns the view without errors. */
    public function test_date_range_filter_applies_without_errors(): void
    {
        $resp = $this->actingAs($this->getAdmin(), 'admin')
            ->get(route('sales.index', [
                'start_date' => Carbon::today()->subDays(2)->toDateString(),
                'end_date' => Carbon::today()->subDay()->toDateString(),
            ]));

        $resp->assertOk();
        $resp->assertViewHas('sales');
        $resp->assertSee('Fecha de venta', false);
    }

    /** Rejects an invalid range where start date is after end date with a validation error. */
    public function test_invalid_date_range_is_rejected(): void
    {
        $resp = $this->actingAs($this->getAdmin(), 'admin')
            ->get(route('sales.reports.byCategory', [
                'date_range' => 'custom',
                'date_from' => Carbon::today()->toDateString(),
                'date_to' => Carbon::today()->subDays(5)->toDateString(),
            ]));

        $resp->assertSessionHasErrors(['date_to']);
    }

    /** Returns a successful response with no sales data when the range has no matching records. */
    public function test_date_range_with_no_results_returns_ok(): void
    {
        $resp = $this->actingAs($this->getAdmin(), 'admin')
            ->get(route('sales.index', [
                'start_date' => Carbon::today()->addDay()->toDateString(),
                'end_date' => Carbon::today()->addDays(2)->toDateString(),
            ]));

        $resp->assertOk();
        $resp->assertViewHas('sales');
    }

    /** Ensures paginator links include the active date filter parameters. */
    public function test_selected_dates_are_preserved_after_filter_is_applied(): void
    {
        $startDate = Carbon::today()->subDays(2)->toDateString();
        $endDate = Carbon::today()->subDay()->toDateString();

        $resp = $this->actingAs($this->getAdmin(), 'admin')
            ->get(route('sales.index', [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]));

        $resp->assertOk();
        $resp->assertViewHas('sales', function ($paginator) use ($startDate, $endDate) {
            $url = $paginator->url(1);

            return str_contains($url, 'start_date='.$startDate)
                && str_contains($url, 'end_date='.$endDate);
        });
    }

    /** Date filter combined with additional filters (payment method, status) applies without errors. */
    public function test_date_range_combined_with_other_filters_applies_without_errors(): void
    {
        $resp = $this->actingAs($this->getAdmin(), 'admin')
            ->get(route('sales.index', [
                'start_date' => Carbon::today()->subDays(2)->toDateString(),
                'end_date' => Carbon::today()->subDay()->toDateString(),
                'payment_method' => 'cash',
                'status' => 'completed',
            ]));

        $resp->assertOk();
        $resp->assertViewHas('sales');
    }
}
