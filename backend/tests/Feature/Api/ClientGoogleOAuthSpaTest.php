<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El flujo OAuth iniciado desde el SPA (?from=spa) redirige al frontend Next
 * en vez de a las rutas web Inertia. Se fuerza la rama de error (sin config de
 * Google) para verificar el destino sin contactar a Google.
 */
class ClientGoogleOAuthSpaTest extends TestCase
{
    use RefreshDatabase;

    public function test_spa_oauth_start_without_config_redirects_to_spa_login(): void
    {
        config(['services.google.client_id' => '', 'services.google.redirect' => '']);
        config(['app.spa_url' => 'http://localhost:3000']);

        $this->get('/auth/google?from=spa')
            ->assertredirectContains('http://localhost:3000/login');
    }

    public function test_web_oauth_start_without_config_stays_on_web(): void
    {
        config(['services.google.client_id' => '', 'services.google.redirect' => '']);

        // Sin ?from=spa el redirect va a la home web (Inertia), no al SPA.
        $response = $this->get('/auth/google');
        $this->assertStringNotContainsString('localhost:3000', (string) $response->headers->get('Location'));
    }
}
