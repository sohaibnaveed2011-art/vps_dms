#!/bin/bash
set -e

echo "🚀 DMS Starting with role: ${SERVICE_ROLE:-app} at $(date)"

# Fix permissions
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# Generate key if missing (should not happen in production)
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
    echo "⚠️ APP_KEY missing, generating one..."
    php artisan key:generate --force --no-interaction
fi

# Wait for database (for roles that need it)
if [[ "$SERVICE_ROLE" != "reverb" && "$SERVICE_ROLE" != "scheduler" ]]; then
    echo "⏳ Waiting for database at ${DB_HOST}:${DB_PORT}..."
    for i in {1..30}; do
        if php artisan db:show > /dev/null 2>&1; then
            echo "✅ Database ready!"
            break
        fi
        echo "⏳ Database not ready yet... ($i/30)"
        sleep 2
    done

    # Run migrations only for app service
    if [ "$SERVICE_ROLE" = "app" ]; then
        echo "📦 Running migrations..."
        php artisan migrate --force --no-interaction || echo "⚠️ Migration issues, but continuing..."
        
        # Clear and cache configurations for production
        echo "📦 Caching configurations..."
        php artisan config:cache --no-interaction
        php artisan route:cache --no-interaction
        php artisan view:cache --no-interaction
        
        # Create storage link if missing
        if [ ! -L public/storage ]; then
            php artisan storage:link --force --no-interaction
        fi
    fi
fi

# Clear old cache for queue worker
if [ "$SERVICE_ROLE" = "queue" ]; then
    echo "🔄 Optimizing queue worker..."
    php artisan queue:flush 2>/dev/null || true
    php artisan cache:clear --no-interaction 2>/dev/null || true
fi

# For reverb, ensure config is cached
if [ "$SERVICE_ROLE" = "reverb" ]; then
    echo "🔊 Preparing Reverb server..."
    php artisan config:cache --no-interaction
fi

echo "✅ Initialization complete. Starting ${SERVICE_ROLE}..."

# Execute the main command
exec "$@"