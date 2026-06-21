#!/bin/bash
set -e

# SQLite database setup
mkdir -p /app/database
touch /app/database/database.sqlite

# Permissions
chown -R www-data:www-data /app/storage /app/bootstrap/cache
chmod -R 775 /app/storage /app/bootstrap/cache

# Laravel bootstrap
cd /app
php artisan package:discover --ansi
php artisan migrate --force
php artisan storage:link || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Fix SQLite permissions after migrations
chown -R www-data:www-data /app/database
chmod 664 /app/database/database.sqlite

# Generate nginx config (envsubst only replaces $PORT, leaves nginx vars untouched)
PORT="${PORT:-80}"
envsubst '$PORT' < /assets/nginx.conf.template > /etc/nginx.conf

# Start supervisor (manages nginx, php-fpm, queue worker)
supervisord -c /assets/supervisord.conf -n
