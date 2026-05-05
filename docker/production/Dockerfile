FROM php:8.3-fpm

# Install system dependencies + Node.js + required extensions
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libpng-dev libonig-dev libicu-dev \
    curl gnupg supervisor nginx \
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
    # Create log file if it doesn't exist
    touch storage/logs/laravel.log && \
    # Set permissions
    chown -R www-data:www-data storage bootstrap/cache public && \
    chmod -R 775 storage bootstrap/cache && \
    chmod -R 775 storage/logs && \
    chmod 664 storage/logs/laravel.log

# Configure PHP for better error logging
RUN echo "error_log = /proc/self/fd/2" >> /usr/local/etc/php/conf.d/docker-php.ini && \
    echo "log_errors = On" >> /usr/local/etc/php/conf.d/docker-php.ini && \
    echo "display_errors = Off" >> /usr/local/etc/php/conf.d/docker-php.ini && \
    echo "error_reporting = E_ALL" >> /usr/local/etc/php/conf.d/docker-php.ini

# Configure PHP-FPM to log to stdout/stderr
RUN echo "catch_workers_output = yes" >> /usr/local/etc/php-fpm.d/docker.conf && \
    echo "decorate_workers_output = no" >> /usr/local/etc/php-fpm.d/docker.conf

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

# Set permissions again after all file operations
RUN chown -R www-data:www-data storage bootstrap/cache public && \
    chmod -R 775 storage bootstrap/cache && \
    chmod -R 775 storage/logs

# Copy nginx configuration (if you switch to nginx+php-fpm)
# COPY ./docker/production/nginx.conf /etc/nginx/sites-enabled/default

# Copy entrypoint script
COPY ./docker/production/docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]