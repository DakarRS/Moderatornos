FROM php:8.4.27-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libicu-dev libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libpq-dev libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" intl zip gd exif bcmath mbstring pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

RUN curl -fsSL https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer --version=2.11.1

# Filament uploads avatars through Livewire; the default 2M cap is too low for photos.
RUN printf 'upload_max_filesize=8M\npost_max_size=10M\nmemory_limit=512M\n' > /usr/local/etc/php/conf.d/app.ini

WORKDIR /app
