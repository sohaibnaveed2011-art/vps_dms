#!/bin/bash
set -e

echo "🚀 Running entrypoint tasks for role: ${SERVICE_ROLE:-unknown}..."

# Fix permissions
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

# Storage link
if [ ! -L public/storage ]; then
    php artisan storage:link --force 2>/dev/null || true
fi

# Generate key if missing
if [ ! -f .env ] || ! grep -q "APP_KEY=" .env || grep -q "APP_KEY=$" .env; then
    echo "🔑 Generating APP_KEY..."
    php artisan key:generate --force
fi

# Wait for database
if [ "$SERVICE_ROLE" != "reverb" ]; then
    echo "⏳ Waiting for database at mariadb:3306..."
    for i in $(seq 1 30); do
        if php artisan db:show > /dev/null 2>&1; then
            echo "✅ Database ready!"
            break
        fi
        echo "⏳ Waiting for database... ($i/30)"
        sleep 2
    done

    # Wait for Redis
    echo "⏳ Waiting for Redis at redis_cache:6379..."
    for i in $(seq 1 30); do
        if php artisan tinker --execute="try { Redis::connection()->ping(); echo 'ok'; } catch(Exception \$e) { exit(1); }" 2>/dev/null | grep -q "ok"; then
            echo "✅ Redis ready!"
            break
        fi
        echo "⏳ Waiting for Redis... ($i/30)"
        sleep 2
    done
fi

# Run migrations only for app service
if [ "$SERVICE_ROLE" = "app" ]; then
    echo "📦 Running migrations..."
    php artisan migrate --force || echo "⚠️ Migration failed, continuing..."
fi

# Start Vite only for app in local environment
if [ "$SERVICE_ROLE" = "app" ] && [ "${APP_ENV:-production}" = "local" ] && [ -f "package.json" ]; then
    echo "⚡ Starting Vite dev server..."
    npm install --silent 2>/dev/null || true
    npm run dev &
fi

echo "✅ Entrypoint complete. Starting service: $@"
exec "$@"