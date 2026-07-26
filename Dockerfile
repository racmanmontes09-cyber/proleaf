FROM php:8.4-cli

WORKDIR /var/www/html

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libzip-dev \
    libpq-dev \
    && docker-php-ext-install pdo_mysql pdo_pgsql zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . .

# Install PHP dependencies
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Create startup script
RUN echo '#!/bin/bash\n\
echo "🔄 Waiting for database to be ready..."\n\
sleep 5\n\
echo "🗄️ Running migrations..."\n\
php artisan migrate --force --no-interaction\n\
echo "🚀 Starting application..."\n\
php artisan serve --host=0.0.0.0 --port=8080\n\
' > /usr/local/bin/start.sh && chmod +x /usr/local/bin/start.sh

EXPOSE 8080

CMD ["/usr/local/bin/start.sh"]