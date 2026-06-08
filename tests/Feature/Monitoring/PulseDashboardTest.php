<?php

namespace Tests\Feature\Monitoring;

use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Seguimiento 8 — monitoreo Laravel Pulse (Dilan).
 */
#[Group('seguimiento8')]
#[Group('seguimiento8-dilan')]
class PulseDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['pulse.enabled' => true]);
    }

    public function test_pulse_monitoring_is_available_for_admin(): void
    {
        $this->artisan('pulse:check', ['--once' => true])->assertSuccessful();

        $this->get('/pulse')->assertForbidden();

        $admin = AdminUser::create([
            'name' => 'Pulse',
            'first_surname' => 'Admin',
            'second_surname' => null,
            'gmail' => 'pulse-admin@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/pulse')
            ->assertOk();
    }
}
