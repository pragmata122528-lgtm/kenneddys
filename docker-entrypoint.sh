#!/bin/sh
set -e

echo "Starting Kings Marketplace API Container..."

# Ensure storage directories exist with write permissions
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Cache configuration & routes for fast performance
php artisan config:cache || true
php artisan route:cache || true

# Run database migrations
echo "Running database migrations..."
php artisan migrate --force || echo "Migration warning: check DB connectivity."

# Start Laravel server
PORT_TO_LISTEN="${PORT:-8000}"
echo "Listening on port ${PORT_TO_LISTEN}..."
exec php artisan serve --host=0.0.0.0 --port="${PORT_TO_LISTEN}"
