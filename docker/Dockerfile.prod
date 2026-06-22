# ============================================
# Production Dockerfile — dormed-shop (dormed 24)
# Multi-stage: Build → FPM+Nginx+Supervisor Runtime
# ============================================

# ---- Build Stage ----
FROM php:8.4-cli-bookworm AS build

# System deps for PHP extensions + build tooling
RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev libpng-dev libonig-dev libxml2-dev libicu-dev libcurl4-openssl-dev \
    unzip ca-certificates curl git \
    && docker-php-ext-install -j$(nproc) pdo pdo_sqlite mbstring zip xml bcmath intl \
    && rm -rf /var/lib/apt/lists/*

# Node.js 22 (build only)
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

# Composer 2
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /build

# Layer 1: PHP dependencies (cache-friendly)
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction --no-progress

# Layer 2: Frontend dependencies (cache-friendly)
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

# Layer 3: App source
COPY . .

# Bootstrap Laravel & build frontend assets
RUN mkdir -p database && touch database/database.sqlite \
    && php artisan package:discover --ansi \
    && php artisan wayfinder:generate --with-form \
    && npm run build \
    && rm -rf node_modules

# ---- Production Stage ----
FROM php:8.4-fpm-bookworm AS production

# Runtime deps: nginx + supervisor + light utilities
RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx supervisor curl gettext-base \
    && docker-php-ext-install -j$(nproc) pdo pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

# Supervisor + Nginx + PHP-FPM configs from project
COPY .nixpacks/assets/supervisord.conf    /assets/supervisord.conf
COPY .nixpacks/assets/worker-nginx.conf   /etc/supervisor/conf.d/worker-nginx.conf
COPY .nixpacks/assets/worker-phpfpm.conf  /etc/supervisor/conf.d/worker-phpfpm.conf
COPY .nixpacks/assets/worker-laravel.conf /etc/supervisor/conf.d/worker-laravel.conf
COPY .nixpacks/assets/php-fpm.conf        /assets/php-fpm.conf
COPY .nixpacks/assets/start.sh            /assets/start.sh
RUN chmod +x /assets/start.sh

# Build artifacts (no node_modules, no .git, no dev deps)
COPY --from=build /build /app

WORKDIR /app

# Runtime env defaults — Coolify / platform overrides these
ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_LEVEL=warning \
    APP_KEY="" \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/app/database/database.sqlite

# Healthcheck: Laravel returns 200 on /
HEALTHCHECK --interval=30s --timeout=3s --start-period=10s --retries=3 \
    CMD curl -f http://localhost:${PORT:-80}/ || exit 1

EXPOSE 80

CMD ["/assets/start.sh"]
