#!/usr/bin/env bash
set -Eeuo pipefail

# Colors (Render logs support plain text; keeping simple)
log()        { echo "[INFO]  $(date +'%Y-%m-%d %H:%M:%S')  $*"; }
log_warn()   { echo "[WARN]  $(date +'%Y-%m-%d %H:%M:%S')  $*"; }
log_error()  { echo "[ERROR] $(date +'%Y-%m-%d %H:%M:%S')  $*" 1>&2; }

log "=== Starting Deployment ==="

# Ensure PHP exists
if ! command -v php >/dev/null 2>&1; then
  log_error "PHP is not installed or not in PATH"
  exit 1
fi

# Install Composer if missing
if ! command -v composer >/dev/null 2>&1; then
  log "Composer not found. Installing Composer..."
  EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "-");')"
  php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
  ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"
  if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]; then
    log_error "Invalid Composer installer checksum"
    rm -f composer-setup.php
    exit 1
  fi
  php composer-setup.php --install-dir=/usr/local/bin --filename=composer >/dev/null
  rm -f composer-setup.php
  if ! command -v composer >/dev/null 2>&1; then
    log_warn "Global Composer install failed; falling back to local composer.phar"
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php composer-setup.php --install-dir=. --filename=composer.phar >/dev/null || true
    rm -f composer-setup.php
    if [ -f ./composer.phar ]; then
      COMPOSER="php ./composer.phar"
    else
      log_error "Composer installation failed"
      exit 1
    fi
  else
    COMPOSER="composer"
  fi
else
  COMPOSER="composer"
fi

# PHP dependencies
log "Installing PHP dependencies..."
$COMPOSER install --no-dev --optimize-autoloader --no-interaction --no-progress

# .env handling
if [ ! -f .env ]; then
  log_warn ".env not found. Copying from .env.example..."
  cp .env.example .env
fi

if ! grep -q "^APP_KEY=base64:" .env 2>/dev/null; then
  log "Generating application key..."
  php artisan key:generate --force || log_warn "Failed to generate APP_KEY"
fi

# Optimize
log "Optimizing application (config/route/view cache)..."
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Frontend build (optional)
if command -v node >/dev/null 2>&1 && command -v npm >/dev/null 2>&1; then
  log "Installing Node dependencies..."
  (npm ci --prefer-offline --no-audit --progress=false || npm install --no-audit --progress=false) || log_warn "Node dependency install failed"
  log "Building assets..."
  npm run build --if-present || log_warn "Asset build failed"
else
  log_warn "Node.js or npm not found. Skipping frontend build."
fi

# Permissions and storage link
log "Setting permissions and storage symlink..."
chmod -R ug+rwX storage bootstrap/cache || true
php artisan storage:link || log_warn "storage:link failed (may already exist)"

log "=== Deployment completed successfully ==="
