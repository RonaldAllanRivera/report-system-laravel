#!/bin/bash
set -e

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Logging function
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING] $1${NC}"
}

log_error() {
    echo -e "${RED}[ERROR] $1${NC}" >&2
    exit 1
}

# Start deployment
log "=== Starting Deployment ==="

# Check for required commands
check_command() {
    if ! command -v $1 &> /dev/null; then
        log_warning "$1 is not installed. Attempting to install..."
        return 1
    fi
    return 0
}

# Install Composer if not exists
if ! check_command composer; then
    log "Installing Composer..."
    EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "-");')"
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"
    
    if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]; then
        log_error 'ERROR: Invalid installer checksum'
        rm composer-setup.php
        exit 1
    fi
    
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer
    RESULT=$?
    rm composer-setup.php
    
    if [ $RESULT -ne 0 ]; then
        log_error 'Failed to install Composer'
    fi
    
    log "Composer installed successfully"
fi

# Install PHP dependencies
log "Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# Generate application key if not exists
if [ ! -f ".env" ]; then
    log_warning ".env file not found. Copying from .env.example..."
    cp .env.example .env
fi

if [ -z "$(grep 'APP_KEY=base64:' .env 2>/dev/null)" ]; then
    log "Generating application key..."
    php artisan key:generate --force
fi

# Optimize application
log "Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Install Node.js and npm if not exists
if ! check_command node || ! check_command npm; then
    log "Installing Node.js..."
    curl -fsSL https://deb.nodesource.com/setup_18.x | bash - && \
    apt-get install -y nodejs || {
        log_error 'Failed to install Node.js'
    }
    log "Node.js installed successfully"
fi

# Install Node.js dependencies
log "Installing Node.js dependencies..."
npm ci --prefer-offline --no-audit --progress=false

# Build assets
log "Building assets..."
npm run build --if-present

# Set correct permissions
log "Setting permissions..."
chmod -R 755 storage bootstrap/cache
chmod -R 775 storage/logs
chmod -R 775 storage/framework/sessions

# Create storage symlink if it doesn't exist
if [ ! -L "public/storage" ]; then
    log "Creating storage symlink..."
    php artisan storage:link
fi

# Run database migrations if needed
# log "Running migrations..."
# php artisan migrate --force

log "=== Deployment completed successfully ==="
