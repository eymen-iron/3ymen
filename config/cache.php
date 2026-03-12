<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Cache Driver
    |--------------------------------------------------------------------------
    | Supported: "auto", "apcu", "file"
    |
    | When set to "auto", the framework will use APCu if available,
    | otherwise it will fall back to file-based caching.
    */
    'driver' => env('CACHE_DRIVER', 'auto'),

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    | A prefix applied to all cache keys to avoid collisions when
    | multiple applications share the same cache store.
    */
    'prefix' => env('CACHE_PREFIX', '3ymen_'),

    /*
    |--------------------------------------------------------------------------
    | File Cache Path
    |--------------------------------------------------------------------------
    | The directory where file-based cache data will be stored.
    */
    'path' => storage_path('cache/data'),

    /*
    |--------------------------------------------------------------------------
    | Default TTL
    |--------------------------------------------------------------------------
    | The default time-to-live in seconds for cached items when
    | no explicit TTL is provided.
    */
    'ttl' => 3600,
];
