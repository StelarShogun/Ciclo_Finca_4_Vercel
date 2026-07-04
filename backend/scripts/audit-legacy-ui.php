#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = realpath(__DIR__.'/..');

if ($root === false) {
    fwrite(STDERR, "Cannot resolve backend root.\n");
    exit(1);
}

$format = 'json';

foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--markdown' || $arg === '--md') {
        $format = 'markdown';
    }

    if ($arg === '--json') {
        $format = 'json';
    }
}

$technicalViewPatterns = [
    '#^admin/exports/#',
    '#^admin/products/.*pdf#',
    '#^admin/reports/.*pdf#',
    '#^admin/sales/(invoice|print|sales-pdf|partials/)#',
    '#^admin/layouts/sales#',
    '#^client/invoice-print#',
    '#^client/layouts/print#',
    '#^components/#',
    '#^emails/#',
    '#^errors/#',
    '#^shared/media/#',
    '#^vendor/pagination/#',
    '#^vendor/pulse/#',
];

$legacyViewPatterns = [
    '#^app$#',
    '#^admin/(dashboard|layouts|partials|parts)/#',
    '#^client/#',
    '#^components/#',
    '#^shared/partials/#',
    '#^vendor/pagination/#',
];

$technicalAssetPatterns = [
    '#^resources/css/admin/(dashboard/dashboard-pdf|products/products-pdf|sales/invoice-document)\.css$#',
    '#^resources/css/admin/(admin-dark-mode|fontawesome|fonts|sidebar|utilities|variables-reset)\.css$#',
    '#^resources/css/admin/components/admin-table\.css$#',
    '#^resources/css/client/(fontawesome|fonts|invoice-print|variables-reset)\.css$#',
    '#^resources/css/errors/#',
    '#^resources/css/shared/(cf4-swal|fontawesome-subset|scrollbars)\.css$#',
    '#^resources/ts/admin/sales/invoice-print\.ts$#',
    '#^resources/ts/client/swal\.ts$#',
    '#^resources/ts/client/invoices-page\.ts$#',
    '#^resources/ts/errors/#',
    '#^resources/ts/shared/(client-pagination|escape-html|legacy-dom)\.ts$#',
];

$legacyAssetPatterns = [
    '#^resources/ts/Pages/#',
    '#^resources/ts/features/#',
    '#^resources/ts/app\.tsx$#',
    '#^resources/css/client/#',
    '#^resources/css/admin/#',
    '#^resources/css/components/#',
    '#^resources/ts/client/#',
    '#^resources/ts/admin/#',
    '#^resources/ts/shared/components/#',
];

$technicalRoutePatterns = [
    '#/internal/vercel#',
    '#^/cron/scheduler$#',
    '#^/jobs/(catalog-import|media-conversions|order-maintenance)$#',
    '#/run-migrations#',
    '#/run-seeders#',
    '#/csrf-token#',
    '#/auth/google#',
    '#/(print|invoice|pdf|excel|exportaciones/descarga|export)#',
    '#/products/suggestions#',
    '#/catalog/(search-trending|heartbeat)#',
    '#/api/#',
    '#^/$#',
    '#^/(catalog|login|auth/login|register|verify|recovery|recovery/verify|recovery/reset|cart|favorites|invoices|notifications|profile|contacto)$#',
    '#^/product/\{id\}/\{slug\?\}$#',
    '#^/invoices/\{sale\}$#',
    '#^/legal/(terminos|privacidad|cambios-devoluciones)$#',
    '#^/admin/login$#',
];

$legacyRoutePatterns = [
    '#/admin/login#',
    '#/dashboard#',
    '#/reports#',
    '#/inventory#',
    '#/classifications#',
    '#/product-classifications#',
    '#/products#',
    '#/suppliers#',
    '#/brands#',
    '#/categories#',
    '#/sales#',
    '#/orders#',
    '#/supplier-orders#',
    '#/clientes#',
    '#^/$#',
    '#/catalog#',
    '#/product/#',
    '#/login#',
    '#/register#',
    '#/verify#',
    '#/recovery#',
    '#/cart#',
    '#/invoices#',
    '#/notifications#',
    '#/profile#',
    '#/favorites#',
    '#/legal/#',
    '#/contacto#',
];

$report = [
    'generated_by' => 'backend/scripts/audit-legacy-ui.php',
    'root' => 'backend',
    'summary' => [
        'delete' => 0,
        'keep-technical' => 0,
        'migrate-first' => 0,
        'unknown' => 0,
    ],
    'findings' => [],
];

$add = static function (array $finding) use (&$report): void {
    $classification = $finding['classification'];
    $report['summary'][$classification] = ($report['summary'][$classification] ?? 0) + 1;
    $report['findings'][] = $finding;
};

$classify = static function (string $value, array $keep, array $delete): string {
    foreach ($keep as $pattern) {
        if (preg_match($pattern, $value) === 1) {
            return 'keep-technical';
        }
    }

    foreach ($delete as $pattern) {
        if (preg_match($pattern, $value) === 1) {
            return 'delete';
        }
    }

    return 'unknown';
};

$relative = static fn (string $path): string => str_replace('\\', '/', substr($path, strlen($root) + 1));

$walk = static function (string $directory, array $extensions = []) use ($root): array {
    $base = $root.'/'.$directory;

    if (! is_dir($base)) {
        return [];
    }

    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));

    foreach ($iterator as $file) {
        if (! $file->isFile()) {
            continue;
        }

        $extension = strtolower($file->getExtension());

        if ($extensions !== [] && ! in_array($extension, $extensions, true)) {
            continue;
        }

        $files[] = $file->getPathname();
    }

    sort($files);

    return $files;
};

foreach ($walk('resources/views', ['php']) as $path) {
    $name = preg_replace('#\.blade\.php$#', '', substr($relative($path), strlen('resources/views/')));
    $classification = $classify($name, $technicalViewPatterns, $legacyViewPatterns);

    $add([
        'kind' => 'blade_view',
        'path' => $relative($path),
        'symbol' => $name,
        'classification' => $classification,
    ]);
}

foreach ($walk('resources/css', ['css']) as $path) {
    $rel = $relative($path);

    $add([
        'kind' => 'asset',
        'path' => $rel,
        'symbol' => $rel,
        'classification' => $classify($rel, $technicalAssetPatterns, $legacyAssetPatterns),
    ]);
}

foreach ($walk('resources/ts', ['ts', 'tsx']) as $path) {
    $rel = $relative($path);

    $add([
        'kind' => 'asset',
        'path' => $rel,
        'symbol' => $rel,
        'classification' => $classify($rel, $technicalAssetPatterns, $legacyAssetPatterns),
    ]);
}

$scanReferences = static function (array $files, array $patterns, string $kind) use ($relative, $add): void {
    foreach ($files as $path) {
        $lines = file($path, FILE_IGNORE_NEW_LINES);

        if ($lines === false) {
            continue;
        }

        foreach ($lines as $index => $line) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $line) !== 1) {
                    continue;
                }

                $rel = $relative($path);
                $symbol = trim($line);
                $classification = 'migrate-first';

                if (
                    preg_match("#return\s+view\s*\(\s*['\"](?:admin\.sales\.|client\.invoice-print)#", $symbol) === 1
                    || ($kind === 'ui_reference' && str_starts_with($rel, 'resources/views/errors/'))
                    || ($kind === 'ui_reference' && str_starts_with($rel, 'resources/views/admin/sales/'))
                    || ($kind === 'ui_reference' && str_starts_with($rel, 'resources/views/client/'))
                ) {
                    $classification = 'keep-technical';
                }

                $add([
                    'kind' => $kind,
                    'path' => $rel,
                    'line' => $index + 1,
                    'symbol' => $symbol,
                    'classification' => $classification,
                ]);

                continue 2;
            }
        }
    }
};

$scanReferences(
    array_merge($walk('app', ['php']), $walk('resources/views', ['php'])),
    ['#Inertia::render\s*\(#', '#return\s+view\s*\(#', '#@vite\s*\(#'],
    'ui_reference'
);

foreach ([$root.'/routes/web.php'] as $path) {
    if (! is_file($path)) {
        continue;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES);

    if ($lines === false) {
        continue;
    }

    foreach ($lines as $index => $line) {
        if (preg_match('#Route::(?:get|post|put|patch|delete|resource)\(([^;]+)#', $line) !== 1) {
            continue;
        }

        preg_match("#['\"]([^'\"]+)['\"]#", $line, $match);
        $uri = $match[1] ?? trim($line);
        $classification = $classify($uri, $technicalRoutePatterns, $legacyRoutePatterns);

        $add([
            'kind' => 'web_route',
            'path' => 'routes/web.php',
            'line' => $index + 1,
            'symbol' => $uri,
            'classification' => $classification,
        ]);
    }
}

usort($report['findings'], static fn (array $a, array $b): int => [$a['classification'], $a['kind'], $a['path'], $a['line'] ?? 0] <=> [$b['classification'], $b['kind'], $b['path'], $b['line'] ?? 0]);

if ($format === 'markdown') {
    echo "# Legacy UI audit\n\n";
    echo "| classification | count |\n| --- | ---: |\n";

    foreach ($report['summary'] as $classification => $count) {
        echo "| {$classification} | {$count} |\n";
    }

    echo "\n| classification | kind | path | line | symbol |\n| --- | --- | --- | ---: | --- |\n";

    foreach ($report['findings'] as $finding) {
        $line = (string) ($finding['line'] ?? '');
        $symbol = str_replace('|', '\\|', $finding['symbol']);
        echo "| {$finding['classification']} | {$finding['kind']} | {$finding['path']} | {$line} | {$symbol} |\n";
    }

    exit(0);
}

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
