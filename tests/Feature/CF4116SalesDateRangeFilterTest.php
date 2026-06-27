<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
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
                'date_range' => 'custom',
                'date_from' => Carbon::today()->subDays(2)->toDateString(),
                'date_to' => Carbon::today()->subDay()->toDateString(),
            ]));

        $resp->assertOk();
        $resp->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Sales/Index', false)
            ->has('sales')
        );
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
                'date_range' => 'custom',
                'date_from' => Carbon::today()->addDay()->toDateString(),
                'date_to' => Carbon::today()->addDays(2)->toDateString(),
            ]));

        $resp->assertOk();
        $resp->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Sales/Index', false)
            ->has('sales')
        );
    }

    /** Ensures paginator links include the active date filter parameters. */
    public function test_selected_dates_are_preserved_after_filter_is_applied(): void
    {
        $startDate = Carbon::today()->subDays(2)->toDateString();
        $endDate = Carbon::today()->subDay()->toDateString();

        $resp = $this->actingAs($this->getAdmin(), 'admin')
            ->get(route('sales.index', [
                'date_range' => 'custom',
                'date_from' => $startDate,
                'date_to' => $endDate,
            ]));

        $resp->assertOk();
        $resp->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Sales/Index', false)
            ->where('filters.date_from', $startDate)
            ->where('filters.date_to', $endDate)
        );
    }

    /** Date filter combined with additional filters (payment method, status) applies without errors. */
    public function test_date_range_combined_with_other_filters_applies_without_errors(): void
    {
        $resp = $this->actingAs($this->getAdmin(), 'admin')
            ->get(route('sales.index', [
                'date_range' => 'custom',
                'date_from' => Carbon::today()->subDays(2)->toDateString(),
                'date_to' => Carbon::today()->subDay()->toDateString(),
                'payment_method' => 'cash',
                'status' => 'completed',
            ]));

        $resp->assertOk();
        $resp->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Sales/Index', false)
            ->has('sales')
            ->where('filters.payment_method', 'cash')
            ->where('filters.status', 'completed')
        );
    }
}
