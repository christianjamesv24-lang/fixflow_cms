#!/bin/sh
set -eu

if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY must be configured in the Vercel project environment." >&2
    exit 1
fi

if [ -z "${DB_URL:-}" ]; then
    for variable in DB_HOST DB_DATABASE DB_USERNAME DB_PASSWORD; do
        if [ -z "$(printenv "$variable" || true)" ]; then
            echo "$variable must be configured, or provide DB_URL." >&2
            exit 1
        fi
    done
fi

if [ -n "${MYSQL_ATTR_SSL_CA_BASE64:-}" ]; then
    printf '%s' "$MYSQL_ATTR_SSL_CA_BASE64" | base64 -d > /tmp/mysql-ca.pem
    chmod 0644 /tmp/mysql-ca.pem
    export MYSQL_ATTR_SSL_CA=/tmp/mysql-ca.pem
fi

storage_path="${LARAVEL_STORAGE_PATH:-/tmp/laravel-storage}"
mkdir -p \
    "$storage_path/app/private" \
    "$storage_path/framework/cache/data" \
    "$storage_path/framework/sessions" \
    "$storage_path/framework/views" \
    "$storage_path/logs"
chown -R www-data:www-data "$storage_path"

port="${PORT:-80}"
sed -i "s/^Listen 80$/Listen ${port}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \\*:80>/<VirtualHost *:${port}>/" /etc/apache2/sites-enabled/000-default.conf

exec apache2-foreground
