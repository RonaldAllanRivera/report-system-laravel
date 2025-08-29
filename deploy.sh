#!/bin/bash
set -e

# Install PHP dependencies
echo "Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# Generate application key if not exists
if [ -z "$(grep 'APP_KEY=base64:' .env)" ]; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

# Optimize application
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Install Node.js dependencies
echo "Installing Node.js dependencies..."
npm ci --prefer-offline

# Build assets
echo "Building assets..."
npm run build

# Set correct permissions
echo "Setting permissions..."
chmod -R 755 storage bootstrap/cache
chmod -R 777 storage/logs

# Run database migrations (uncomment if needed)
# echo "Running migrations..."
# php artisan migrate --force

echo "Deployment completed successfully!"
