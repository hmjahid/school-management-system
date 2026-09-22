# Eskoofy raw PHP variant — dev image (PHP CLI).
#
# The app has no runtime framework dependencies, so the image is just PHP
# with the PDO/MySQL + gd/mbstring/zip extensions the app uses.

FROM php:8.3-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev \
        libjpeg62-turbo-dev \
        libzip-dev \
        libonig-dev \
        libxml2-dev \
        libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring exif gd zip fileinfo \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

EXPOSE 8051