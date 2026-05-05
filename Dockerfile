FROM php:8.3-fpm

# Install system dependencies
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

# Create health check endpoint
RUN mkdir -p public && echo "<?php echo 'ok';" > public/health

# Install PHP dependencies
RUN if [ -f "composer.json" ]; then \
        composer install --no-interaction --no-scripts --prefer-dist || true; \
    fi

# Install NPM dependencies (if exists)
RUN if [ -f "package.json" ]; then \
        npm install --silent || true; \
    fi

# Set permissions
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache 2>/dev/null || true

EXPOSE 8000

# Default command (can be overridden)
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]