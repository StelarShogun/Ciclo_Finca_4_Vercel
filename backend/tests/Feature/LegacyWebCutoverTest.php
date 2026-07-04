<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegacyWebCutoverTest extends TestCase
{
    public function test_legacy_page_routes_redirect_to_next_paths(): void
    {
        config(['app.spa_url' => 'https://next.example.test']);

        $this->get('/catalog')->assertRedirect('https://next.example.test/catalog');
        $this->get('/login')->assertRedirect('https://next.example.test/login');
        $this->get('/admin/login')->assertRedirect('https://next.example.test/admin/login');
    }

    public function test_production_code_has_no_inertia_render_calls(): void
    {
        $backendRoot = dirname(__DIR__, 2);
        $paths = [
            $backendRoot.'/app',
            $backendRoot.'/bootstrap',
            $backendRoot.'/config',
            $backendRoot.'/routes',
            $backendRoot.'/resources',
        ];

        $cmd = 'rg -n '.escapeshellarg('Inertia::render|HandleInertiaRequests|use Inertia\\\\')
            .' '.implode(' ', array_map('escapeshellarg', $paths));

        exec($cmd, $output, $code);

        $this->assertSame(1, $code, implode("\n", $output));
    }
}
