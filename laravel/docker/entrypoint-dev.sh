#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

mkdir -p \
  storage/app/private/{invoices,collection-notices,sales} \
  storage/app/public \
  storage/framework/{cache/data,sessions,views} \
  storage/logs \
  bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache

exec docker-php-entrypoint php-fpm "$@"
