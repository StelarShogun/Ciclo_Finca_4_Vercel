<?php

namespace Tests\Feature;

use App\Http\Middleware\LogSensitiveAdminModuleAccess;
use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImportCatalogRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(LogSensitiveAdminModuleAccess::class);
    }

    private function actingAsAdmin(): AdminUser
    {
        $admin = AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'Import',
            'second_surname' => null,
            'gmail' => 'admin-import-validation@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);

        $this->actingAs($admin, 'admin');

        return $admin;
    }

    public function test_missing_file_returns_spanish_json_validation_error(): void
    {
        $this->actingAsAdmin();

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->post(route('products.import'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['import_file']);
        $this->assertStringContainsString(
            'seleccion',
            strtolower((string) $response->json('errors.import_file.0')),
        );
    }

    public function test_file_too_large_for_laravel_rule_returns_spanish_message(): void
    {
        $this->actingAsAdmin();

        $file = UploadedFile::fake()->create('catalogo.zip', 102401, 'application/zip');

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->post(route('products.import'), [
            'import_file' => $file,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['import_file']);
        $this->assertStringContainsString('100 mb', strtolower((string) $response->json('errors.import_file.0')));
    }
}
