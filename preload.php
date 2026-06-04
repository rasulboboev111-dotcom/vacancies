<?php

/*
|--------------------------------------------------------------------------
| OPcache preload script
|--------------------------------------------------------------------------
|
| Preloads the framework, packages and application classes into OPcache shared
| memory once at server start, so they are never re-compiled per request
| (typically a large TTFB win on top of plain OPcache).
|
| Enable on the PRODUCTION server only (preloaded code is frozen until the
| php-fpm process restarts — never use this in development). In php.ini:
|
|   opcache.preload=/path/to/app/preload.php
|   opcache.preload_user=www-data
|
| Run `php artisan optimize` before/while deploying so the cached config,
| routes and events are warm too.
|
*/

if (! function_exists('opcache_compile_file') || ! ini_get('opcache.enable')) {
    return;
}

if (! is_file(__DIR__.'/vendor/composer/autoload_classmap.php')) {
    return;
}

require __DIR__.'/vendor/autoload.php';

/** @var array<string, string> $classMap */
$classMap = require __DIR__.'/vendor/composer/autoload_classmap.php';

foreach ($classMap as $file) {
    // Skip the app's own deferred providers' migrations/tests and anything that
    // executes on include; only compile framework + app + first-party packages.
    if (! is_string($file) || ! is_file($file)) {
        continue;
    }

    // Compilation of a class whose parent isn't loaded yet only warns — suppress
    // so a single un-linkable file never aborts the whole preload.
    @opcache_compile_file($file);
}
