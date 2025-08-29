# syntax=docker/dockerfile:1

# Build assets with Node
FROM node:20-alpine AS assets
WORKDIR /app
# Copy only files needed for faster caching; fall back to full copy if needed
COPY package.json package-lock.json* vite.config.js ./
COPY resources ./resources
# Install and build (ignore audit for speed)
RUN npm ci --no-audit --progress=false || npm install --no-audit --progress=false
RUN npm run build || true

# Production PHP + Apache image
FROM php:8.2-apache

# Install system dependencies and PHP extensions
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
       git curl zip unzip \
       libpng-dev libjpeg62-turbo-dev libfreetype6-dev libonig-dev libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring exif pcntl bcmath gd \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache modules
RUN a2enmod rewrite headers expires

# Set working directory
WORKDIR /var/www/html

# Install Composer (from official image)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy application code
COPY . /var/www/html

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# Copy built assets from Node stage if present
COPY --from=assets /app/public/build /var/www/html/public/build

# Apache vhost to point to public/
COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf

# Entrypoint handles Laravel bootstrap and migrations, then starts Apache
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
