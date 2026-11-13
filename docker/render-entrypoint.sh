#!/bin/sh
set -eu

if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY must be configured in the Render environment." >&2
    exit 1
fi

case "$APP_KEY" in
    base64:*) ;;
    *) export APP_KEY="base64:$APP_KEY" ;;
esac

if [ -z "${APP_URL:-}" ] && [ -n "${RENDER_EXTERNAL_HOSTNAME:-}" ]; then
    export APP_URL="https://${RENDER_EXTERNAL_HOSTNAME}"
fi

mkdir -p \
    storage/app/private \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs
chown -R www-data:www-data storage bootstrap/cache

php artisan storage:link --force

if [ "${DEMO_MODE:-false}" = "true" ]; then
    if [ "${DB_CONNECTION:-}" != "sqlite" ] || [ -z "${DB_DATABASE:-}" ] || [ "${DB_DATABASE:-}" = ":memory:" ]; then
        echo "Demo mode requires a file-backed SQLite database." >&2
        exit 1
    fi

    mkdir -p "$(dirname "$DB_DATABASE")"
    touch "$DB_DATABASE"
    chown www-data:www-data "$DB_DATABASE"
    chmod 660 "$DB_DATABASE"
    php artisan migrate --force
    php artisan admin:create-demo
fi

php artisan config:cache

exec apache2-foreground
