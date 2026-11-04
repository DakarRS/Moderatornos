FROM dunglas/frankenphp:1-php8.4.27-bookworm AS base
RUN install-php-extensions intl zip gd exif bcmath pdo_pgsql opcache
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && printf 'upload_max_filesize=8M\npost_max_size=10M\nmemory_limit=256M\nexpose_php=Off\n' > "$PHP_INI_DIR/conf.d/app.ini"

FROM base AS build
RUN apt-get update && apt-get install -y --no-install-recommends git unzip && rm -rf /var/lib/apt/lists/*
RUN curl -fsSL https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer --version=2.11.1
WORKDIR /app
COPY . .
RUN cp .env.example .env \
    && composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts \
    && php artisan key:generate --force \
    && php artisan package:discover \
    && php artisan filament:assets \
    && rm .env

FROM base
RUN apt-get update && apt-get install -y --no-install-recommends libcap2-bin \
    && setcap -r /usr/local/bin/frankenphp \
    && apt-get purge -y libcap2-bin && apt-get autoremove -y && rm -rf /var/lib/apt/lists/*
WORKDIR /app
COPY --from=build --chown=www-data:www-data /app /app
COPY --chmod=755 docker/backend-start.sh /usr/local/bin/backend-start
RUN mkdir -p storage/app/public storage/app/private storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache /data/caddy /config/caddy
ENV SERVER_NAME=:8000 APP_ENV=production APP_DEBUG=false LOG_CHANNEL=stderr
USER www-data
EXPOSE 8000
CMD ["backend-start"]
