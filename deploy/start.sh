#!/bin/bash
set -e

cd /app

# Ensure storage directory structure exists (for Fly.io volume mount)
mkdir -p /app/storage/framework/{sessions,views,cache}
mkdir -p /app/storage/logs
mkdir -p /app/storage/app/public

# Create SQLite database if using sqlite
if [ "$DB_CONNECTION" = "sqlite" ]; then
    touch /app/database/database.sqlite
fi

# Generate key if not set
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    php artisan key:generate --force
fi

# Run migrations
php artisan migrate --force

# Cache config, routes, views for performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create storage link
php artisan storage:link --force 2>/dev/null || true

# Start Reverb in background (WebSocket server)
php artisan reverb:start --host=0.0.0.0 --port=8080 &

# Start the Laravel server
php artisan serve --host=0.0.0.0 --port=8000
