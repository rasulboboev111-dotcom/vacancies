#!/bin/sh
set -e

# ── Wait for Postgres ────────────────────────────────────────────────────────
# The db service has a healthcheck, but a short PDO probe makes the app, queue
# and scheduler containers resilient to start-order races on their own.
echo "Waiting for database at ${DB_HOST:-db}:${DB_PORT:-5432}..."
until php -r '
    $h = getenv("DB_HOST") ?: "db";
    $p = getenv("DB_PORT") ?: "5432";
    $d = getenv("DB_DATABASE");
    $u = getenv("DB_USERNAME");
    $w = getenv("DB_PASSWORD");
    try { new PDO("pgsql:host=$h;port=$p;dbname=$d", $u, $w); }
    catch (Throwable $e) { exit(1); }
' 2>/dev/null; do
    sleep 2
done
echo "Database is up."

# ── Migrations (app container only) ──────────────────────────────────────────
# RUN_MIGRATIONS is set on the app service so the queue/scheduler replicas do
# not race to migrate the same database.
if [ "${RUN_MIGRATIONS}" = "true" ]; then
    echo "Running migrations..."
    php artisan migrate --force
    php artisan storage:link 2>/dev/null || true

    # Publish built assets to the volume nginx serves (kept fresh on every start).
    if [ -d /var/www/html/public-dist ]; then
        cp -a public/. /var/www/html/public-dist/ 2>/dev/null || true
    fi
fi

# ── Optimized caches ─────────────────────────────────────────────────────────
# Built at runtime so they capture the environment injected by compose. Only
# config:cache is required; the rest are best-effort optimizations.
php artisan config:cache
php artisan event:cache || true
php artisan view:cache || true
php artisan route:cache || true

exec "$@"
