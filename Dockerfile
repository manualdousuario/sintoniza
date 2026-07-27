# syntax=docker/dockerfile:1

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
RUN install-php-extensions gd intl tidy
COPY --chmod=755 docker/entrypoint.d/ /etc/entrypoint.d/
COPY --chmod=755 docker/s6-rc.d/ /etc/s6-overlay/s6-rc.d/
USER www-data

WORKDIR /var/www/html

COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install --no-interaction --no-dev --prefer-dist --no-scripts --no-autoloader

COPY --chown=www-data:www-data . .
RUN composer dump-autoload --optimize --no-dev

COPY --from=assets --chown=www-data:www-data /app/public/assets/css/styles.css /var/www/html/public/assets/css/styles.css

EXPOSE 8080
