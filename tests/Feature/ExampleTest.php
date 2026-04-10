<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        // Ruta de salud de Laravel (no depende de tablas ni del home con catálogo).
        $response = $this->get('/up');

        $response->assertStatus(200);
    }
}
