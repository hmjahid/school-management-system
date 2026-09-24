# Eskoofy branding website — dev image (PHP CLI).
#
# No framework at runtime; the image only needs PHP with the PDO/MySQL +
# curl/mbstring/gd/zip extensions the site and its gateway adapters use.

FROM php:8.3-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev \
        libjpeg62-turbo-dev \
        libzip-dev \
        libonig-dev \
        libxml2-dev \
        libfreetype6-dev \
        libcurl4-openssl-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring exif gd zip fileinfo curl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

EXPOSE 8052