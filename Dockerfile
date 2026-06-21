FROM php:8.4-fpm-bookworm

# System deps + PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx supervisor curl gettext-base \
    libzip-dev libpng-dev libonig-dev libxml2-dev libsqlite3-dev libicu-dev libcurl4-openssl-dev \
    && docker-php-ext-install pdo pdo_sqlite mbstring zip xml bcmath intl \
    && rm -rf /var/lib/apt/lists/*

# Node.js 22
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Build-time env vars needed for artisan commands
ARG APP_KEY
ARG APP_ENV=production
ARG DB_CONNECTION=sqlite
ARG DB_DATABASE=/app/database/database.sqlite
ENV APP_KEY=$APP_KEY APP_ENV=$APP_ENV DB_CONNECTION=$DB_CONNECTION DB_DATABASE=$DB_DATABASE

WORKDIR /app

# PHP deps as separate layer (cache-friendly)
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# App source
COPY . .

# Bootstrap Laravel, then generate Wayfinder types before frontend build
RUN mkdir -p database && touch database/database.sqlite \
    && php artisan package:discover --ansi \
    && php artisan wayfinder:generate --with-form

# Frontend build
RUN npm install && npm run build && rm -rf node_modules

# Supervisor + config files
RUN mkdir -p /etc/supervisor/conf.d /assets
COPY .nixpacks/assets/supervisord.conf    /assets/supervisord.conf
COPY .nixpacks/assets/worker-nginx.conf   /etc/supervisor/conf.d/worker-nginx.conf
COPY .nixpacks/assets/worker-phpfpm.conf  /etc/supervisor/conf.d/worker-phpfpm.conf
COPY .nixpacks/assets/worker-laravel.conf /etc/supervisor/conf.d/worker-laravel.conf
COPY .nixpacks/assets/php-fpm.conf        /assets/php-fpm.conf
COPY .nixpacks/assets/start.sh            /assets/start.sh
RUN chmod +x /assets/start.sh

EXPOSE 80

CMD ["/assets/start.sh"]
