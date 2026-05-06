#!/bin/sh
set -e

cd /var/www/html

mkdir -p \
  storage/app/public \
  storage/framework/cache \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs \
  bootstrap/cache

if [ "$(id -u)" = "0" ]; then
  chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
fi

exec "$@"
