<?php

namespace Tests\Feature\Api;

use App\Jobs\RunCatalogImportJob;
use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * API v1 admin: importación de catálogo. Encola el job, expone el importId y
 * el progreso queda disponible para hacer polling.
 */
class InventoryImportApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['sanctum.stateful' => ['localhost', 'localhost:3000', '127.0.0.1']]);
        $this->withHeader('Origin', 'http://localhost:3000');
        Storage::fake('local');
    }

    private function admin(): AdminUser
    {
        return AdminUser::firstOrCreate(
            ['gmail' => 'import-admin@example.com'],
            ['name' => 'Imp', 'first_surname' => 'Admin', 'second_surname' => null, 'password' => bcrypt('password123'), 'last_access' => now()],
        );
    }

    public function test_import_requires_authentication(): void
    {
        $this->postJson('/api/v1/admin/inventory/import')->assertStatus(401);
    }

    public function test_import_queues_job_and_returns_import_id(): void
    {
        Queue::fake();
        $this->actingAs($this->admin(), 'admin');

        $file = UploadedFile::fake()->createWithContent('catalogo.json', '[]');

        $res = $this->post('/api/v1/admin/inventory/import', ['import_file' => $file])
            ->assertStatus(202)
            ->assertJsonStructure(['importId', 'progress']);

        Queue::assertPushed(RunCatalogImportJob::class);

        // El progreso quedó registrado y es consultable por el endpoint de polling.
        $importId = $res->json('importId');
        $this->getJson("/api/v1/admin/inventory/import/{$importId}/progress")
            ->assertOk()
            ->assertJsonPath('status', 'queued');
    }

    public function test_import_rejects_unsupported_extension(): void
    {
        Queue::fake();
        $this->actingAs($this->admin(), 'admin');

        $file = UploadedFile::fake()->create('catalogo.pdf', 10);

        $this->postJson('/api/v1/admin/inventory/import', ['import_file' => $file])
            ->assertStatus(422)
            ->assertJsonValidationErrors('import_file');

        Queue::assertNothingPushed();
    }

    public function test_progress_unknown_for_missing_import(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->getJson('/api/v1/admin/inventory/import/no-existe/progress')
            ->assertStatus(404)
            ->assertJsonPath('status', 'unknown');
    }
}
