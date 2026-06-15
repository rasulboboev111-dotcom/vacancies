# ─────────────────────────────────────────────────────────────────────────────
# Stage 1 — vendor: install PHP dependencies without dev packages or scripts
# (package:discover runs later in the app stage where the extensions exist).
# ─────────────────────────────────────────────────────────────────────────────
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction
COPY . .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative

# ─────────────────────────────────────────────────────────────────────────────
# Stage 2 — frontend: compile the Vite/Vue assets (public/build is git-ignored,
# so it MUST be built here). Produces /app/public with source + built assets.
# The Ziggy Vue plugin ships inside the Composer package, so resources/js/app.js
# imports it from vendor/ — pull that one package in from the vendor stage.
# ─────────────────────────────────────────────────────────────────────────────
FROM node:22-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
COPY --from=vendor /app/vendor/tightenco/ziggy ./vendor/tightenco/ziggy
RUN npm run build

# ─────────────────────────────────────────────────────────────────────────────
# Stage 3 — app: the runtime PHP-FPM image (php-fpm, queue worker, scheduler).
# ─────────────────────────────────────────────────────────────────────────────
FROM php:8.2-fpm-alpine AS app
WORKDIR /var/www/html

# Runtime libs kept; build deps installed as a virtual package and removed after.
RUN apk add --no-cache libpq icu-libs libzip oniguruma \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS libpq-dev icu-dev libzip-dev oniguruma-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql pgsql mbstring bcmath intl zip opcache pcntl \
    && apk del .build-deps

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini

# Application code, vendored deps, and the compiled front-end bundle.
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build

COPY --from=frontend /app/public/build /tmp/public_build
# Cache the package manifest (needs the PHP extensions above) and fix writable dirs.
RUN php artisan package:discover --ansi \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]

# ─────────────────────────────────────────────────────────────────────────────
# Stage 4 — web: nginx serving the static assets and proxying PHP to app:9000.
# It carries its own copy of public/ so there is no shared-volume staleness.
# ─────────────────────────────────────────────────────────────────────────────
FROM nginx:alpine AS web
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
