<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Global Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware listed here will be applied to every action executed
    | through the executor, in the order they are defined.
    |
    */

    'middleware' => [
        \ShafiMsp\Actions\Middleware\CacheMiddleware::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Handler Cache
    |--------------------------------------------------------------------------
    |
    | The executor can cache handler and return-type mappings so they don't
    | need to be resolved via reflection on every request. Run `actions:cache`
    | to generate the cache and `actions:clear` to remove it.
    |
    */

    'cache' => [

        /*
         * Enable or disable handler caching entirely.
         */
        'enabled' => true,

        /*
         * Directories to scan when discovering action classes during
         * cache generation. Defaults to the `app/` directory.
         */
        'directories' => [app_path()],

        /*
         * The file path where the cached manifest will be stored.
         * When null, defaults to `bootstrap/cache/actions.php`.
         */
        'path' => null,

    ],

];
