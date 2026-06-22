<?php

return [
    'enabled' => env('APP_PLATFORM') === 'vercel',
    'qstash_token' => env('QSTASH_TOKEN'),
    'qstash_base_url' => env('QSTASH_BASE_URL', 'https://qstash.upstash.io/v2'),
    'job_delay_seconds' => (int) env('VERCEL_JOB_DELAY_SECONDS', 1),
    'import_disk' => env('VERCEL_IMPORT_DISK', 'vercel_blob'),
    'import_prefix' => env('VERCEL_IMPORT_PREFIX', 'catalog-imports'),
];
