<?php

namespace Tests\Unit\Services;

use App\Services\Admin\Audit\AuditPayloadSanitizer;
use PHPUnit\Framework\TestCase;

final class AuditPayloadSanitizerTest extends TestCase
{
    public function test_it_masks_sensitive_nested_payload_values(): void
    {
        $safe = (new AuditPayloadSanitizer)->sanitize([
            'sale_id' => 10,
            'buyer_email' => 'client@example.test',
            'nested' => [
                'token' => 'secret-token',
                'status' => 'completed',
            ],
        ]);

        $this->assertSame(10, $safe['sale_id']);
        $this->assertSame('[masked]', $safe['buyer_email']);
        $this->assertSame('[masked]', $safe['nested']['token']);
        $this->assertSame('completed', $safe['nested']['status']);
    }
}
