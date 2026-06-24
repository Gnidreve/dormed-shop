#!/bin/bash
set -e

echo "=== dormed-shop Update ==="
echo ""

# 1. Git pull
echo "📦 Pulling latest code..."
git pull origin main

# 2. Composer install
echo ""
echo "📦 Installing/updating Composer dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader

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
php artisan migrate --force

# 5. Clear caches
echo ""
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
php artisan optimize:clear

# 6. Restart queue (if running)
echo ""
echo "🔄 Restarting queue workers..."
php artisan queue:restart 2>/dev/null || true

# 7. Re-cache for production
echo ""
echo "⚡ Re-caching production config..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 8. Set permissions (Laravel storage)
echo ""
echo "🔐 Setting storage permissions..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

echo ""
echo "✅ Update complete!"
echo "   $(date)"
