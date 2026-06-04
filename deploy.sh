#!/usr/bin/env bash
#
# Production deploy / optimization script.
#
# Run this ON THE PRODUCTION SERVER (not on a dev machine — it strips dev
# dependencies and freezes the cached config/routes against the current .env).
#
# Prerequisites on the server:
#   * OPcache enabled in php.ini, ideally with preloading:
#       opcache.enable=1
#       opcache.enable_cli=0
#       opcache.memory_consumption=256
#       opcache.max_accelerated_files=20000
#       opcache.validate_timestamps=0     ; never revalidates -> redeploy to refresh
#       opcache.preload=/path/to/app/preload.php
#       opcache.preload_user=www-data
#
set -euo pipefail

echo "==> Installing production dependencies (no dev, authoritative classmap)"
composer install --no-dev --optimize-autoloader --classmap-authoritative --no-interaction --prefer-dist

echo "==> Building frontend assets"
npm ci
npm run build

echo "==> Caching framework (config + routes + events + views)"
php artisan optimize

echo "==> Running database migrations"
php artisan migrate --force

echo "==> Restarting workers so they pick up new code"
php artisan queue:restart

# If using OPcache preloading, reload php-fpm so the preload script re-runs and
# validate_timestamps=0 doesn't serve stale bytecode:
#   sudo systemctl reload php8.4-fpm

echo "==> Done. (To roll back the framework cache during debugging: php artisan optimize:clear)"
