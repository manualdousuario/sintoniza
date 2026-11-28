# syntax=docker/dockerfile:1

FROM serversideup/php:8.4-fpm-nginx

ENV TZ=UTC \
    PHP_OPCACHE_ENABLE=1 \
    HEALTHCHECK_PATH=/up \
    AUTORUN_ENABLED=true \
    AUTORUN_LARAVEL_MIGRATION_TIMEOUT=300

USER root
RUN install-php-extensions gd intl
COPY --chmod=755 docker/entrypoint.d/ /etc/entrypoint.d/
COPY --chmod=755 docker/s6-rc.d/ /etc/s6-overlay/s6-rc.d/
COPY --chmod=755 docker/user-bundles.d/ /etc/s6-overlay/user-bundles.d/
USER www-data

WORKDIR /var/www/html

COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install --no-interaction --no-dev --prefer-dist --no-scripts --no-autoloader

COPY --chown=www-data:www-data . .
RUN composer dump-autoload --optimize --no-dev

EXPOSE 8080
