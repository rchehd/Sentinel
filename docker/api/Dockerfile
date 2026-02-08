# docker/api/Dockerfile

# syntax=docker/dockerfile:1

# Versions
ARG PHP_VERSION=8.4
ARG FRANKENPHP_VERSION=1

# Base stage with FrankenPHP
FROM dunglas/frankenphp:${FRANKENPHP_VERSION}-php${PHP_VERSION} AS base

# Install system dependencies
RUN apt-get update && apt-get install -y \
    acl \
    file \
    gettext \
    git \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN install-php-extensions \
    apcu \
    intl \
    opcache \
    pdo_pgsql \
    pgsql \
    redis \
    zip

# Configure PHP
COPY docker/api/conf.d/10-app.ini $PHP_INI_DIR/conf.d/

# Set working directory
WORKDIR /app

# Copy Caddyfile
COPY docker/api/Caddyfile /etc/caddy/Caddyfile

# Copy Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ========================================
# Development stage
# ========================================
FROM base AS dev

ENV APP_ENV=dev \
    XDEBUG_MODE=off

# Install XDebug for debugging
RUN install-php-extensions xdebug

# Development PHP config
COPY docker/api/conf.d/20-app.dev.ini $PHP_INI_DIR/conf.d/

# Set permission for FrankenPHP
RUN mkdir -p /app/var && \
    setfacl -R -m u:www-data:rwX -m u:"$(whoami)":rwX /app/var && \
    setfacl -dR -m u:www-data:rwX -m u:"$(whoami)":rwX /app/var

VOLUME /app

# ========================================
# Builder stage
# ========================================
FROM base AS builder

# Copy application code
COPY app/api /app

# Install dependencies
RUN composer install --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress

# Generate autoloader
RUN composer dump-autoload --classmap-authoritative --no-dev

# Clear and warmup cache
RUN APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear && \
    APP_ENV=prod APP_DEBUG=0 php bin/console cache:warmup

# ========================================
# Production stage
# ========================================
FROM base AS prod

ENV APP_ENV=prod \
    APP_DEBUG=0

# Production PHP config
COPY docker/api/conf.d/20-app.prod.ini $PHP_INI_DIR/conf.d/

# Copy built application from builder
COPY --from=builder --chown=www-data:www-data /app /app

# Set proper permissions
RUN mkdir -p /app/var && \
    chown -R www-data:www-data /app/var && \
    chmod -R 755 /app/var

# Health check
HEALTHCHECK --interval=10s --timeout=3s --retries=3 \
    CMD curl -f http://localhost/api/health || exit 1

EXPOSE 80 443