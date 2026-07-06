<?php

namespace Tests\Unit\Services;

use App\Models\Sale;
use App\Services\Admin\Sales\OrderStatusPolicy;
use PHPUnit\Framework\TestCase;

class OrderStatusPolicyTest extends TestCase
{
    private OrderStatusPolicy $policy;

    protected function setUp(): void
    {
        $this->policy = new OrderStatusPolicy;
    }

    public function test_allowed_transitions_for_order_workflow(): void
    {
        $this->assertTrue($this->policy->markReadyToPickup($this->sale('pending'))['allowed']);
        $this->assertTrue($this->policy->complete($this->sale('ready_to_pickup'))['allowed']);
        $this->assertTrue($this->policy->cancel($this->sale('pending'))['allowed']);
        $this->assertTrue($this->policy->cancel($this->sale('ready_to_pickup'))['allowed']);
        $this->assertTrue($this->policy->destroy($this->sale('pending'))['allowed']);
        $this->assertTrue($this->policy->returnSale($this->sale('completed'))['allowed']);
    }

    public function test_rejected_transitions_keep_existing_messages(): void
    {
        $this->assertSame(
            'El pedido debe estar en estado "Listo para recoger" antes de confirmarse.',
            $this->policy->complete($this->sale('pending'))['message'],
        );

        $this->assertSame(
            'No se puede rechazar un pedido ya confirmado. Use devolución si aplica.',
            $this->policy->cancel($this->sale('completed'))['message'],
        );

        $this->assertSame(
            'Solo las ventas confirmadas pueden registrar una devolución.',
            $this->policy->returnSale($this->sale('ready_to_pickup'))['message'],
        );
    }

    public function test_ready_to_pickup_is_idempotent(): void
    {
        $result = $this->policy->markReadyToPickup($this->sale('ready_to_pickup'));

        $this->assertTrue($result['allowed']);
        $this->assertTrue($result['already_done']);
    }

    private function sale(string $status): Sale
    {
        return new Sale(['status' => $status]);
    }
}
