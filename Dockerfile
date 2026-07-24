FROM php:8.2-fpm-alpine

RUN apk add --no-cache postgresql-dev libzip-dev zip icu-dev oniguruma-dev linux-headers \
    && docker-php-ext-install pdo pdo_pgsql zip bcmath intl opcache pcntl sockets

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
