#!/usr/bin/env bash
set -euo pipefail

log() { echo "[INFO]  $(date +'%Y-%m-%d %H:%M:%S')  $*"; }
warn() { echo "[WARN]  $(date +'%Y-%m-%d %H:%M:%S')  $*"; }
err() { echo "[ERROR] $(date +'%Y-%m-%d %H:%M:%S')  $*" 1>&2; }

cd /var/www/html

# Ensure storage and cache are writable by the web server
chown -R www-data:www-data storage bootstrap/cache || warn "chown failed; continuing..."
chmod -R ug+rwX storage bootstrap/cache || warn "chmod failed; continuing..."

# Generate app key if it's not set in the environment
if [ -z "${APP_KEY:-}" ]; then
    log "Generating APP_KEY"
    # This will generate a key and add it to the .env file if it exists,
    # but since we don't have one, it won't persist, which is fine.
    # The primary goal is to have a key for the cache commands.
    php artisan key:generate --force
fi

# Clear caches before startup
log "Clearing caches"
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

# Run migrations to ensure DB is ready (best-effort)
log "Running migrations"
php artisan migrate --force || warn "Migrations failed; continuing to start Apache"

# Cache config/routes/views for performance (best-effort)
log "Caching Laravel config/routes/views"
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Storage link (best-effort)
php artisan storage:link || true

## Ensure Apache binds to Render's PORT
PORT_ENV="${PORT:-10000}"
log "Configuring Apache to listen on PORT=${PORT_ENV}"
# Update ports.conf to listen on the desired port
if grep -qE '^\s*Listen\s+80\b' /etc/apache2/ports.conf 2>/dev/null; then
  sed -ri "s/^\s*Listen\s+80\b/Listen ${PORT_ENV}/" /etc/apache2/ports.conf || true
else
  echo "Listen ${PORT_ENV}" > /etc/apache2/ports.conf || true
fi
# Update the vhost to use the same port
if [ -f /etc/apache2/sites-available/000-default.conf ]; then
  sed -ri "s#<VirtualHost \*:80>#<VirtualHost *:${PORT_ENV}>#" /etc/apache2/sites-available/000-default.conf || true
fi

# Hand off to Apache foreground
exec apache2-foreground
