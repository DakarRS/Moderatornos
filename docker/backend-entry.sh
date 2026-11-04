#!/bin/sh
set -e
composer install --no-interaction --prefer-dist
[ -f .env ] || { cp .env.example .env; php artisan key:generate --force; }
php artisan migrate --force
php artisan storage:link --force
php artisan db:seed --force
exec php artisan serve --host=0.0.0.0 --port=8000
