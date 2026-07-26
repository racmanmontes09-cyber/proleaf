FROM php:8.4-cli

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libzip-dev \
    && docker-php-ext-install pdo_mysql zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install --no-dev --optimize-autoloader

RUN php artisan config:cache
RUN php artisan route:cache
RUN php artisan view:cache

EXPOSE 8080

CMD php artisan serve --host=0.0.0.0 --port=8080