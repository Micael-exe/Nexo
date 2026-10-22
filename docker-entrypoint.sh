#!/bin/sh
set -e
PORT="${PORT:-10000}"
sed -ri "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf
# O disco persistente é montado em storage/app e começa vazio.
mkdir -p storage/app/data storage/app/private storage/app/public \
         storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs
chown -R www-data:www-data storage bootstrap/cache
if [ "${CANAL_DEMO:-0}" = "1" ]; then
  php artisan canal:demo
fi
php artisan config:cache
php artisan route:cache
php artisan view:cache
exec "$@"
