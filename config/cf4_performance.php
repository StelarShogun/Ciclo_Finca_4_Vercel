<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Storefront read cache (seconds)
    |--------------------------------------------------------------------------
    |
    | TTLs for Cache::remember on shared catalog data. Uses the default cache
    | store (file/redis). Lower values if admins need changes visible instantly.
    |
    */
    'client_root_categories_ttl' => (int) env('CF4_CACHE_CLIENT_CATEGORIES', 600),
    'client_brands_catalog_ttl' => (int) env('CF4_CACHE_CLIENT_BRANDS', 300),
    'client_catalog_spotlight_ttl' => (int) env('CF4_CACHE_CLIENT_SPOTLIGHT', 120),

    /*
    |--------------------------------------------------------------------------
    | Admin dashboard aggregate cache (seconds)
    |--------------------------------------------------------------------------
    |
    | Only the HTML dashboard index uses this; exports and JSON endpoints stay
    | uncached for fresher numbers.
    |
    */
    'admin_dashboard_index_ttl' => (int) env('CF4_CACHE_ADMIN_DASHBOARD', 60),

];
