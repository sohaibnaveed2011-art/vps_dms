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

# Create necessary directories
RUN mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache

# Install PHP dependencies (without dev dependencies for production)
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

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache public && \
    chmod -R 775 storage bootstrap/cache

# Copy entrypoint script
COPY docker/production/docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]