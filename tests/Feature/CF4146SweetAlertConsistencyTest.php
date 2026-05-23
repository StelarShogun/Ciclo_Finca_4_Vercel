<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CF4146SweetAlertConsistencyTest extends TestCase
{
    public function test_no_native_browser_alert_confirm_or_prompt_remain_in_resources(): void
    {
        $paths = [
            resource_path('js'),
            resource_path('views'),
        ];

        $violations = [];

        foreach ($paths as $path) {
            if (! File::exists($path)) {
                continue;
            }

            foreach (File::allFiles($path) as $file) {
                $relative = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file->getPathname());

                if (! str_ends_with($relative, '.js') && ! str_ends_with($relative, '.blade.php')) {
                    continue;
                }

                $contents = File::get($file->getPathname());

                if (preg_match_all('/\b(window\.)?(alert|confirm|prompt)\s*\(/', $contents, $matches, PREG_OFFSET_CAPTURE)) {
                    foreach ($matches[0] as $match) {
                        $line = substr_count(substr($contents, 0, $match[1]), "\n") + 1;
                        $violations[] = "{$relative}:{$line} contiene {$match[0]}";
                    }
                }
            }
        }

        $this->assertSame([], $violations, "No deben quedar alert(), confirm() ni prompt() nativos:\n" . implode("\n", $violations));
    }
}
