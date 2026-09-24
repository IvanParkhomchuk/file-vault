#!/bin/sh
set -eu

mkdir -p storage/app/private storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs

if [ -z "${APP_KEY:-}" ]; then
    key_file=storage/app/private/.app-key
    if [ ! -s "$key_file" ]; then
        php -r 'echo "base64:", base64_encode(random_bytes(32));' > "$key_file"
        chmod 600 "$key_file"
    fi
    APP_KEY=$(cat "$key_file")
    export APP_KEY
fi

if [ "${1:-}" = php ] && [ "${2:-}" = artisan ] && [ "${3:-}" = serve ]; then
    php artisan migrate --force
fi

exec "$@"
