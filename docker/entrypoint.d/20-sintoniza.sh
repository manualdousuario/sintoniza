#!/bin/bash

set -e

cd /var/www/html

mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    storage/logs

if [ -z "${APP_KEY:-}" ]; then
    echo "[sintoniza] APP_KEY is not set, generating a temporary key."
    echo "[sintoniza] Set APP_KEY in your compose file to keep sessions valid across restarts."

    [ -f .env ] || : >.env
    grep -q '^APP_KEY=' .env || echo 'APP_KEY=' >>.env

    php artisan key:generate --force --no-interaction
fi
