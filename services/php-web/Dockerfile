FROM php:8.3-fpm-alpine

# пакеты и расширения (добавлены libzip-dev, libpng-dev для PHPSpreadsheet)
RUN apk add --no-cache bash curl git unzip rsync postgresql-dev dos2unix \
    libzip-dev libpng-dev freetype-dev libjpeg-turbo-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install pdo pdo_pgsql zip gd

# composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# каталоги
RUN mkdir -p /var/www/html /opt/laravel-patches
WORKDIR /var/www/html

# entrypoint
COPY entrypoint.sh /usr/local/bin/php_entrypoint.sh
RUN dos2unix /usr/local/bin/php_entrypoint.sh || sed -i 's/\r$//' /usr/local/bin/php_entrypoint.sh \
    && chmod +x /usr/local/bin/php_entrypoint.sh

CMD ["bash", "/usr/local/bin/php_entrypoint.sh"]
