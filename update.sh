#!/bin/bash
set -e

echo "=== dormed-shop Update ==="
echo ""

# Helper: run artisan safely (ignores DevCommands warning)
artisan() {
    php artisan "$@" 2>&1 | grep -v "DevCommands\|Exception\|vendor/laravel" || true
}

# 1. Git pull
echo "📦 Pulling latest code..."
git pull origin main

# 2. Composer install (skip scripts to avoid DevCommands warning)
echo ""
echo "📦 Installing/updating Composer dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Run post-install steps manually
echo ""
echo "🔧 Running post-install tasks..."
php artisan package:discover --ansi 2>/dev/null || echo "   (package:discover warning ignored — non-critical)"
php artisan storage:link --force 2>/dev/null || true

# 3. NPM install & build
echo ""
echo "📦 Installing NPM dependencies..."
npm install --no-audit --no-fund

echo ""
echo "🔨 Building frontend assets..."
npm run build

# 4. Run migrations
echo ""
echo "🗄️  Running database migrations..."
php artisan migrate --force --no-interaction 2>/dev/null || php artisan migrate --force --no-interaction

# 5. Clear caches
echo ""
echo "🧹 Clearing caches..."
php artisan config:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true

# 6. Restart queue (if running)
echo ""
echo "🔄 Restarting queue workers..."
php artisan queue:restart 2>/dev/null || true

# 7. Re-cache for production
echo ""
echo "⚡ Re-caching production config..."
php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true
php artisan view:cache 2>/dev/null || true

# 8. Set permissions (Laravel storage)
echo ""
echo "🔐 Setting storage permissions..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

echo ""
echo "✅ Update complete!"
echo "   $(date)"
