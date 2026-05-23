<?php

namespace Tests\Unit;

use App\Models\Sale;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SaleAdminDateLabelsTest extends TestCase
{
    #[Test]
    public function format_admin_datetime_uses_dd_mm_yyyy_hh_mm(): void
    {
        $value = Carbon::parse('2026-05-20 14:30:00', 'UTC');

        $this->assertMatchesRegularExpression(
            '/^\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}$/',
            Sale::formatAdminDateTime($value),
        );
    }

    #[Test]
    public function confirmed_label_only_when_sale_is_completed(): void
    {
        $pending = new Sale([
            'status' => 'pending',
            'sale_date' => now(),
            'updated_at' => now(),
        ]);
        $this->assertSame('—', $pending->adminConfirmedAtLabel());

        $completed = new Sale([
            'status' => 'completed',
            'sale_date' => now(),
        ]);
        $completed->updated_at = Carbon::parse('2026-05-20 09:15:00');
        $this->assertNotSame('—', $completed->adminConfirmedAtLabel());
    }
}
