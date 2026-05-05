FROM php:8.3-cli

# Install system dependencies + Node.js + required extensions
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libpng-dev libonig-dev libicu-dev \
    curl gnupg \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && docker-php-ext-install pdo_mysql mysqli sockets zip mbstring gd intl bcmath pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy application files
COPY . .

# Create necessary directories with proper permissions
RUN mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache && \
    touch storage/logs/laravel.log && \
    chmod -R 775 storage bootstrap/cache && \
    chmod 666 storage/logs/laravel.log

# CRITICAL: Force ALL PHP errors to laravel.log
RUN echo "error_log = /app/storage/logs/laravel.log" >> /usr/local/etc/php/conf.d/docker-php.ini && \
    echo "log_errors = On" >> /usr/local/etc/php/conf.d/docker-php.ini && \
    echo "display_errors = Off" >> /usr/local/etc/php/conf.d/docker-php.ini && \
    echo "error_reporting = E_ALL" >> /usr/local/etc/php/conf.d/docker-php.ini

# Install PHP dependencies
RUN if [ -f "composer.json" ]; then \
        composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader; \
    fi

# Install NPM dependencies and build assets
RUN if [ -f "package.json" ]; then \
        npm install --production=false && \
        npm run build || echo "No build script found"; \
    fi

# Create health check endpoint
RUN echo "<?php header('HTTP/1.1 200 OK'); echo 'ok';" > public/health

# Create storage link
RUN php artisan storage:link --force || true

# Copy entrypoint script
COPY ./docker/production/docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]