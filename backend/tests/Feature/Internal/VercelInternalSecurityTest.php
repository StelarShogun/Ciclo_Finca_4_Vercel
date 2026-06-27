<?php

namespace Tests\Feature\Internal;

use Tests\TestCase;

final class VercelInternalSecurityTest extends TestCase
{
    public function test_internal_vercel_jobs_reject_query_string_secret(): void
    {
        config(['app.deploy_secret' => 'test-secret']);

        $this->postJson('/internal/vercel/jobs/catalog-import?key=test-secret')
            ->assertNotFound();
    }

    public function test_internal_vercel_jobs_accept_deploy_secret_header(): void
    {
        config(['app.deploy_secret' => 'test-secret']);

        $this->postJson('/internal/vercel/jobs/catalog-import', [], [
            'X-Deploy-Secret' => 'test-secret',
        ])->assertUnprocessable();
    }

    public function test_deploy_helper_rejects_invalid_seeder_class(): void
    {
        $this->getJson('/run-seeders/FooSeeder')
            ->assertBadRequest()
            ->assertJson([
                'ok' => false,
                'message' => 'Seeder no válido.',
            ]);
    }
}
