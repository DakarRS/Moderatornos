#!/bin/sh
set -e
php artisan storage:link --force
# Cached here rather than in the image, because config comes from the environment at runtime.
php artisan optimize
php artisan filament:optimize
exec frankenphp run --config /etc/frankenphp/Caddyfile
