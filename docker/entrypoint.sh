#!/bin/bash
set -e

# Copy .env if not exists
if [ ! -f .env ]; then
    cp .env.example .env
    php artisan key:generate
fi

# Wait for database to be ready
echo "Waiting for database..."
until php -r "new PDO('pgsql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}');" 2>/dev/null; do
    sleep 2
done
echo "Database is ready."

# Run migrations
php artisan migrate --force

# Clear and cache config
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Start Laravel dev server
exec php artisan serve --host=0.0.0.0 --port=8000
