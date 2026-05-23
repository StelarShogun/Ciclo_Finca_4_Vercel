<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CF4146SweetAlertConsistencyTest extends TestCase
{
    /** @var list<string> */
    private array $scanRoots = [];

    /** @var list<array{path: string, relative: string}> */
    private array $jsAndBladeFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->scanRoots = [
            resource_path('js'),
            resource_path('views'),
        ];

        $this->jsAndBladeFiles = $this->collectJsAndBladeFiles();
    }

    public function test_no_native_browser_alert_confirm_or_prompt_remain_in_resources(): void
    {
        $violations = [];

        foreach ($this->jsAndBladeFiles as $file) {
            $contents = File::get($file['path']);
            $relative = $file['relative'];

            if (preg_match_all('/\b(window\.)?(alert|confirm|prompt)\s*\(/', $contents, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $line = substr_count(substr($contents, 0, $match[1]), "\n") + 1;
                    $violations[] = "{$relative}:{$line} contiene {$match[0]}";
                }
            }
        }

        $this->assertSame([], $violations, "No deben quedar alert(), confirm() ni prompt() nativos:\n".implode("\n", $violations));
    }

    public function test_no_await_cf4_loading_in_resources_js(): void
    {
        $violations = [];

        foreach ($this->jsAndBladeFiles as $file) {
            if (! str_ends_with($file['relative'], '.js')) {
                continue;
            }

            $contents = File::get($file['path']);

            if (preg_match_all('/\bawait\s+cf4Loading\s*\(/', $contents, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $line = substr_count(substr($contents, 0, $match[1]), "\n") + 1;
                    $violations[] = "{$file['relative']}:{$line} usa await cf4Loading (bloquea el fetch)";
                }
            }
        }

        $this->assertSame([], $violations, implode("\n", $violations));
    }

    public function test_no_legacy_fire_swal_three_argument_calls(): void
    {
        $violations = [];

        foreach ($this->jsAndBladeFiles as $file) {
            if (! str_ends_with($file['relative'], '.js')) {
                continue;
            }

            $contents = File::get($file['path']);

            if (preg_match_all("/\bfireSwal\s*\(\s*['\"]/", $contents, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $line = substr_count(substr($contents, 0, $match[1]), "\n") + 1;
                    $violations[] = "{$file['relative']}:{$line} usa fireSwal con API legacy de 3 argumentos";
                }
            }
        }

        $this->assertSame([], $violations, implode("\n", $violations));
    }

    public function test_no_sweetalert_inline_colors_remain(): void
    {
        $violations = [];

        foreach ($this->jsAndBladeFiles as $file) {
            $contents = File::get($file['path']);
            $relative = $file['relative'];

            if (preg_match('/confirmButtonColor|cancelButtonColor/', $contents)) {
                $violations[] = "{$relative} contiene confirmButtonColor o cancelButtonColor";
            }
        }

        $this->assertSame([], $violations, implode("\n", $violations));
    }

    public function test_no_direct_swal_fire_outside_helpers(): void
    {
        $allowed = [
            'resources/js/admin/shared/swal.js',
            'resources/js/client/swal.js',
            'resources/js/client/invoices-review-modal.js',
        ];

        $violations = [];

        foreach ($this->jsAndBladeFiles as $file) {
            if (! str_ends_with($file['relative'], '.js')) {
                continue;
            }

            if (in_array($file['relative'], $allowed, true)) {
                continue;
            }

            $contents = File::get($file['path']);

            if (preg_match_all('/\bSwal\.fire\s*\(/', $contents, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $line = substr_count(substr($contents, 0, $match[1]), "\n") + 1;
                    $violations[] = "{$file['relative']}:{$line} usa Swal.fire fuera de helpers permitidos";
                }
            }
        }

        $this->assertSame([], $violations, implode("\n", $violations));
    }

    /**
     * @return list<array{path: string, relative: string}>
     */
    private function collectJsAndBladeFiles(): array
    {
        $files = [];

        foreach ($this->scanRoots as $path) {
            if (! File::exists($path)) {
                continue;
            }

            foreach (File::allFiles($path) as $file) {
                $relative = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname());

                if (! str_ends_with($relative, '.js') && ! str_ends_with($relative, '.blade.php')) {
                    continue;
                }

                $files[] = [
                    'path' => $file->getPathname(),
                    'relative' => $relative,
                ];
            }
        }

        return $files;
    }
}
