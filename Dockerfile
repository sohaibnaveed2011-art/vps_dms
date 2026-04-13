FROM php:8.3-fpm

# 1. Install system dependencies + Node.js (for Vite/Laravel 12)
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libpng-dev libonig-dev libicu-dev \
    curl gnupg \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-install pdo pdo_mysql sockets zip mbstring gd intl bcmath pcntl

# 2. Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# 3. Copy files and install dependencies
COPY . .
RUN composer install --no-interaction --no-scripts --prefer-dist

# 4. Permissions
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache

# 5. Laravel 12+ Development Command
# We use 'php artisan serve' for local dev, or 'php-fpm' for production
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]