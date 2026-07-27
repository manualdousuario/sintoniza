# syntax=docker/dockerfile:1

# Stage 1: build the site's Tailwind CSS (Filament/admin assets are built
# separately by `php artisan filament:assets`, not part of this stage).
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM serversideup/php:8.4-fpm-nginx

ENV TZ=UTC \
    PHP_OPCACHE_ENABLE=1 \
    HEALTHCHECK_PATH=/up \
    AUTORUN_ENABLED=true \
    AUTORUN_LARAVEL_MIGRATION_TIMEOUT=300

USER root
# gd: gregwar/captcha, intl: filament/support, tidy: DescriptionFormatter.
RUN install-php-extensions gd intl tidy
COPY --chmod=755 docker/entrypoint.d/ /etc/entrypoint.d/
COPY --chmod=755 docker/s6-rc.d/ /etc/s6-overlay/s6-rc.d/
USER www-data

WORKDIR /var/www/html

# Dependencies first so application edits do not invalidate the vendor layer.
COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install --no-interaction --no-dev --prefer-dist --no-scripts --no-autoloader

COPY --chown=www-data:www-data . .
RUN composer dump-autoload --optimize --no-dev

# Overwrite the checked-in placeholder with the CSS actually built from
# resources/css/app.css.
COPY --from=assets --chown=www-data:www-data /app/public/assets/css/styles.css /var/www/html/public/assets/css/styles.css

EXPOSE 8080
