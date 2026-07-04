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
        $violations = [];

        foreach (['app', 'bootstrap', 'config', 'routes', 'resources'] as $dir) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($backendRoot.'/'.$dir, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if (! $file->isFile()) {
                    continue;
                }

                $contents = file_get_contents($file->getPathname());
                if ($contents !== false && preg_match('/Inertia::render|HandleInertiaRequests|use Inertia\\\\/', $contents) === 1) {
                    $violations[] = str_replace($backendRoot.'/', '', $file->getPathname());
                }
            }
        }

        $this->assertSame([], $violations);
    }
}
