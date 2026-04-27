<?php

namespace Tests\Feature;

use App\Http\Middleware\LogSensitiveAdminModuleAccess;
use App\Models\AdminUser;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * CF4-41 — bitácora de auditoría (CP41-01 a CP41-04).
 */
class AuditLogReportTest extends TestCase
{
    use RefreshDatabase;

    protected AdminUser $adminUser;

    protected function setUp(): void
    {
        try {
            parent::setUp();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Base de datos no disponible: '.$e->getMessage());
        }

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('AuditLogReportTest requiere MySQL.');
        }

        if (! Schema::hasTable('audit_logs') || ! Schema::hasTable('admins')) {
            $this->markTestSkipped('Faltan tablas necesarias para auditoría.');
        }

        // Evita registros automáticos module_access durante el GET de la propia vista.
        $this->withoutMiddleware(LogSensitiveAdminModuleAccess::class);

        $this->adminUser = AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'CF41',
            'second_surname' => null,
            'gmail' => 'admin-cf41@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);
    }

    /** CP41-01 — lista cronológica por defecto (más reciente primero). */
    public function test_cp41_01_lists_audit_logs_in_descending_order_by_default(): void
    {
        $older = AuditLog::query()->create([
            'admin_user_id' => $this->adminUser->user_id,
            'admin_email_snapshot' => $this->adminUser->gmail,
            'action_type' => 'module_access',
            'module' => 'dashboard',
            'description' => 'Registro antiguo',
            'meta' => null,
            'created_at' => '2026-04-24 10:00:00',
        ]);

        $newer = AuditLog::query()->create([
            'admin_user_id' => $this->adminUser->user_id,
            'admin_email_snapshot' => $this->adminUser->gmail,
            'action_type' => 'admin_login',
            'module' => 'auth',
            'description' => 'Registro reciente',
            'meta' => null,
            'created_at' => '2026-04-24 12:00:00',
        ]);

        $response = $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.reports.audit-log'));

        $response->assertOk();
        $response->assertSee('Bitácora de auditoría');
        $response->assertSeeInOrder([
            $newer->description,
            $older->description,
        ]);
    }

    /** CP41-02 — filtros por usuario, tipo, módulo y rango de fechas. */
    public function test_cp41_02_filters_by_user_action_module_and_date_range(): void
    {
        $otherAdmin = AdminUser::create([
            'name' => 'Beatriz',
            'first_surname' => 'Auditora',
            'second_surname' => null,
            'gmail' => 'beatriz.cf41@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);

        AuditLog::query()->create([
            'admin_user_id' => $this->adminUser->user_id,
            'admin_email_snapshot' => $this->adminUser->gmail,
            'action_type' => 'module_access',
            'module' => 'reports',
            'description' => 'Debe aparecer por filtros',
            'meta' => null,
            'created_at' => '2026-04-20 09:00:00',
        ]);

        AuditLog::query()->create([
            'admin_user_id' => $this->adminUser->user_id,
            'admin_email_snapshot' => $this->adminUser->gmail,
            'action_type' => 'admin_login',
            'module' => 'auth',
            'description' => 'No debe aparecer: tipo distinto',
            'meta' => null,
            'created_at' => '2026-04-20 09:30:00',
        ]);

        AuditLog::query()->create([
            'admin_user_id' => $otherAdmin->user_id,
            'admin_email_snapshot' => $otherAdmin->gmail,
            'action_type' => 'module_access',
            'module' => 'reports',
            'description' => 'No debe aparecer: usuario distinto',
            'meta' => null,
            'created_at' => '2026-04-20 09:10:00',
        ]);

        AuditLog::query()->create([
            'admin_user_id' => $this->adminUser->user_id,
            'admin_email_snapshot' => $this->adminUser->gmail,
            'action_type' => 'module_access',
            'module' => 'reports',
            'description' => 'No debe aparecer: fuera de rango',
            'meta' => null,
            'created_at' => '2026-04-25 08:00:00',
        ]);

        $response = $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.reports.audit-log', [
                'user' => 'admin-cf41@',
                'action_type' => 'module_access',
                'module' => 'reports',
                'from' => '2026-04-19',
                'to' => '2026-04-21',
            ]));

        $response->assertOk();
        $response->assertSee('Debe aparecer por filtros');
        $response->assertDontSee('No debe aparecer: tipo distinto');
        $response->assertDontSee('No debe aparecer: usuario distinto');
        $response->assertDontSee('No debe aparecer: fuera de rango');
    }

    /** CP41-03 — muestra estado vacío cuando no hay coincidencias. */
    public function test_cp41_03_shows_empty_state_when_filters_return_no_results(): void
    {
        AuditLog::query()->create([
            'admin_user_id' => $this->adminUser->user_id,
            'admin_email_snapshot' => $this->adminUser->gmail,
            'action_type' => 'admin_login',
            'module' => 'auth',
            'description' => 'Registro existente',
            'meta' => null,
            'created_at' => '2026-04-24 12:00:00',
        ]);

        $response = $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.reports.audit-log', [
                'user' => 'sin-coincidencias@example.com',
            ]));

        $response->assertOk();
        $response->assertSee('Sin registros para los filtros aplicados');
        $response->assertSee('Probá cambiar usuario, módulo, tipo de acción o rango de fechas.');
    }

    /** CP41-04 — permite orden asc/desc y bloquea acceso guest. */
    public function test_cp41_04_supports_ascending_order_and_guest_is_redirected(): void
    {
        AuditLog::query()->create([
            'admin_user_id' => $this->adminUser->user_id,
            'admin_email_snapshot' => $this->adminUser->gmail,
            'action_type' => 'module_access',
            'module' => 'dashboard',
            'description' => 'Primero cronológicamente',
            'meta' => null,
            'created_at' => '2026-04-24 08:00:00',
        ]);

        AuditLog::query()->create([
            'admin_user_id' => $this->adminUser->user_id,
            'admin_email_snapshot' => $this->adminUser->gmail,
            'action_type' => 'module_access',
            'module' => 'reports',
            'description' => 'Después cronológicamente',
            'meta' => null,
            'created_at' => '2026-04-24 10:00:00',
        ]);

        $ascResponse = $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.reports.audit-log', ['dir' => 'asc']));

        $ascResponse->assertOk();
        $ascResponse->assertSeeInOrder([
            'Primero cronológicamente',
            'Después cronológicamente',
        ]);

        auth('admin')->logout();

        $this->get(route('admin.reports.audit-log'))
            ->assertRedirect(route('admin.login'));
    }
}

