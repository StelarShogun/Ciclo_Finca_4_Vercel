<?php

namespace Tests\Unit;

use App\Services\Client\Storefront\ClientPickupPolicy;
use PHPUnit\Framework\TestCase;

class ClientPickupPolicyTest extends TestCase
{
    public function test_window_label_uses_business_days_when_hours_are_multiples_of_24(): void
    {
        $this->assertSame('3 días hábiles', ClientPickupPolicy::windowLabelFromHours(72));
    }

    public function test_summary_line_mentions_pickup_window(): void
    {
        $line = ClientPickupPolicy::summaryLineFromHours(72);

        $this->assertStringContainsString('3 días hábiles', $line);
        $this->assertStringContainsString('retirarlo en tienda', $line);
    }
}
