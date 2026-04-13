FROM php:8.3-fpm

# 1. Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libicu-dev \
    libpq-dev \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-install pdo pdo_mysql sockets zip mbstring gd exif pcntl bcmath intl

# 2. Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 3. Set Working Directory
WORKDIR /app

# 4. Copy the project files (This was missing!)
# This copies everything from your local folder into the /app folder in the image
COPY . .

# 5. Install Production Dependencies
# This ensures vendor folder exists inside the image
RUN composer install --no-dev --optimize-autoloader --no-interaction

# 6. Set Permissions
# Laravel needs write access to these folders
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache \
    && chmod -R 775 /app/storage /app/bootstrap/cache

# 7. Default command
# Use 0.0.0.0 so it is accessible from outside the container
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
