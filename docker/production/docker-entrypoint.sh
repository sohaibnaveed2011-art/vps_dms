#!/bin/bash
set -e

echo "🚀 DMS Starting with role: ${SERVICE_ROLE:-app} at $(date)"

# ============================================================
# LOGGING SETUP - Force everything to laravel.log
# ============================================================

# Ensure laravel.log exists and has correct permissions
touch /app/storage/logs/laravel.log
chmod 666 /app/storage/logs/laravel.log

# Create a function to log messages to both console and laravel.log
log_message() {
    local level="$1"
    local message="$2"
    local timestamp=$(date '+Y-m-d H:i:s')
    echo "[$timestamp] $level: $message" | tee -a /app/storage/logs/laravel.log
}

# Redirect all script output to laravel.log as well
exec 1> >(tee -a /app/storage/logs/laravel.log)
exec 2> >(tee -a /app/storage/logs/laravel.log >&2)

# ============================================================
# PERMISSIONS
# ============================================================

log_message "INFO" "Setting up permissions..."

# Fix permissions (run as root only)
if [ "$(id -u)" = "0" ]; then
    chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
    chmod -R 775 storage bootstrap/cache 2>/dev/null || true
    chmod -R 775 storage/logs 2>/dev/null || true
    chmod 666 storage/logs/laravel.log 2>/dev/null || true
fi

# ============================================================
# APP KEY
# ============================================================

# Generate key if missing
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
    log_message "WARNING" "APP_KEY missing, generating one..."
    php artisan key:generate --force --no-interaction >> /app/storage/logs/laravel.log 2>&1
fi

# ============================================================
# DATABASE & MIGRATIONS
# ============================================================

# Wait for database (for roles that need it)
if [[ "$SERVICE_ROLE" != "reverb" && "$SERVICE_ROLE" != "scheduler" ]]; then
    log_message "INFO" "Waiting for database at ${DB_HOST}:${DB_PORT}..."
    
    DB_READY=false
    for i in {1..30}; do
        if php artisan db:show > /dev/null 2>&1; then
            log_message "INFO" "✅ Database ready!"
            DB_READY=true
            break
        fi
        log_message "INFO" "⏳ Database not ready yet... ($i/30)"
        sleep 2
    done

    if [ "$DB_READY" = false ]; then
        log_message "ERROR" "Database connection failed after 30 attempts"
        # Continue anyway, might work later
    fi

    # Run migrations only for app service
    if [ "$SERVICE_ROLE" = "app" ]; then
        log_message "INFO" "Running migrations..."
        php artisan migrate --force --no-interaction >> /app/storage/logs/laravel.log 2>&1 || log_message "WARNING" "Migration issues, but continuing..."
        
        log_message "INFO" "Caching configurations..."
        php artisan config:cache --no-interaction >> /app/storage/logs/laravel.log 2>&1
        php artisan route:cache --no-interaction >> /app/storage/logs/laravel.log 2>&1
        php artisan view:cache --no-interaction >> /app/storage/logs/laravel.log 2>&1
        
        # Create storage link if missing
        if [ ! -L public/storage ]; then
            log_message "INFO" "Creating storage link..."
            php artisan storage:link --force --no-interaction >> /app/storage/logs/laravel.log 2>&1
        fi
    fi
fi

# ============================================================
# ROLE-SPECIFIC OPTIMIZATIONS
# ============================================================

# Clear old cache for queue worker
if [ "$SERVICE_ROLE" = "queue" ]; then
    log_message "INFO" "Optimizing queue worker..."
    php artisan queue:flush 2>/dev/null >> /app/storage/logs/laravel.log 2>&1 || true
    php artisan cache:clear --no-interaction >> /app/storage/logs/laravel.log 2>&1 || true
fi

# For reverb, ensure config is cached
if [ "$SERVICE_ROLE" = "reverb" ]; then
    log_message "INFO" "Preparing Reverb server..."
    php artisan config:cache --no-interaction >> /app/storage/logs/laravel.log 2>&1
fi

# ============================================================
# PHP ERROR HANDLING - Force all PHP errors to laravel.log
# ============================================================

# Set PHP error logging to laravel.log for the current session
export PHP_ERROR_LOG="/app/storage/logs/laravel.log"

# Create a PHP error handler wrapper
cat > /tmp/php_error_handler.php << 'EOF'
<?php
// Custom error handler to ensure all errors go to laravel.log
$logFile = '/app/storage/logs/laravel.log';

set_error_handler(function($errno, $errstr, $errfile, $errline) use ($logFile) {
    $level = match($errno) {
        E_ERROR => 'FATAL',
        E_WARNING => 'WARNING',
        E_NOTICE => 'NOTICE',
        E_USER_ERROR => 'USER ERROR',
        E_USER_WARNING => 'USER WARNING',
        E_USER_NOTICE => 'USER NOTICE',
        default => 'UNKNOWN'
    };
    
    $message = date('Y-m-d H:i:s') . " [PHP::$level] $errstr in $errfile on line $errline\n";
    file_put_contents($logFile, $message, FILE_APPEND);
    return false;
});

register_shutdown_function(function() use ($logFile) {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $message = date('Y-m-d H:i:s') . " [PHP::FATAL] {$error['message']} in {$error['file']} on line {$error['line']}\n";
        file_put_contents($logFile, $message, FILE_APPEND);
    }
});

// Auto-prepend this file for all PHP processes
file_put_contents('/usr/local/etc/php/conf.d/auto_prepend.ini', 'auto_prepend_file = /tmp/php_error_handler.php');
EOF

# Apply the PHP error handler
php -r "require '/tmp/php_error_handler.php';" || true

# ============================================================
# STARTUP COMPLETE
# ============================================================

log_message "INFO" "✅ Initialization complete. Starting ${SERVICE_ROLE}..."
log_message "INFO" "All logs will be written to: /app/storage/logs/laravel.log"

# Create a tail process to monitor logs (optional, for debugging)
if [ "$SERVICE_ROLE" = "app" ] && [ "${LOG_MONITOR:-false}" = "true" ]; then
    tail -f /app/storage/logs/laravel.log &
fi

# Execute the main command with output redirected to laravel.log
if [ "$SERVICE_ROLE" = "app" ]; then
    # For app service, capture all output
    exec "$@" 2>&1 | tee -a /app/storage/logs/laravel.log
else
    # For other services, just execute
    exec "$@"
fi