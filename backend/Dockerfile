# Tatlas backend (Laravel API + admin).
# Base image ships PHP-FPM + Nginx with pdo_pgsql, opcache, redis, zip.
FROM serversideup/php:8.4-fpm-nginx AS base

USER root
# intl: locale-aware formatting; gd + exif: thumbnails and EXIF orientation/stripping.
RUN install-php-extensions intl gd exif
USER www-data

WORKDIR /var/www/html

# --- development: code is bind-mounted by docker-compose ---------------------
FROM base AS dev

# --- production: code and optimized vendor baked into the image --------------
FROM base AS prod
COPY --chown=www-data:www-data . .
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
