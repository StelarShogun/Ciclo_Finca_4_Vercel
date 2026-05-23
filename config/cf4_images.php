<?php

return [
    'max_upload_bytes' => 10 * 1024 * 1024,
    'client_compress_threshold' => 500 * 1024,
    'client_max_dimension' => 1920,
    'client_webp_quality' => 0.8,
    'variants' => [
        'thumb' => ['width' => 96, 'target_kb' => 20, 'min_quality' => 50, 'max_quality' => 70],
        'card' => ['width' => 480, 'target_kb' => 80, 'min_quality' => 50, 'max_quality' => 76],
        'detail' => ['width' => 1200, 'target_kb' => 180, 'min_quality' => 60, 'max_quality' => 80],
    ],
    'quality_steps' => [80, 70, 60, 50],
    'temp_retention_days' => 1,
];
