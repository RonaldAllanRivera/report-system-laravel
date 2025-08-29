#!/usr/bin/env bash
set -euo pipefail

log() { echo "[INFO]  $(date +'%Y-%m-%d %H:%M:%S')  $*"; }
warn() { echo "[WARN]  $(date +'%Y-%m-%d %H:%M:%S')  $*"; }
err() { echo "[ERROR] $(date +'%Y-%m-%d %H:%M:%S')  $*" 1>&2; }

cd /var/www/html

# Ensure storage and cache are writable
chmod -R ug+rwX storage bootstrap/cache || true

# Ensure .env exists
if [ ! -f .env ]; then
  warn ".env not found; copying from .env.example"
  cp .env.example .env || true
fi

# Generate app key if needed
if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
  log "Generating APP_KEY"
  php artisan key:generate --force || warn "APP_KEY generation failed"
fi

# Cache config/routes/views (best-effort)
log "Caching Laravel config/routes/views"
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Storage link (best-effort)
php artisan storage:link || true

# Run migrations (best-effort for zero-downtime start)
log "Running migrations"
php artisan migrate --force || warn "Migrations failed; continuing to start Apache"

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
