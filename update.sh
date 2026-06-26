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

# 3. Clear Laravel caches before route generation/build
echo ""
echo "🧹 Clearing Laravel caches before build..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# 4. NPM install & build
echo ""
echo "📦 Installing NPM dependencies..."
npm install --no-audit --no-fund

echo ""
echo "⚙️  Generating Wayfinder routes..."
php artisan wayfinder:generate --with-form

echo ""
echo "🔨 Building frontend assets..."
npm run build

# 5. Run migrations
echo ""
echo "🗄️  Running database migrations..."
php artisan migrate --force

# 6. Clear caches
echo ""
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# 7. Restart queue (if running)
echo ""
echo "🔄 Restarting queue workers..."
php artisan queue:restart 2>/dev/null || true

# 8. Re-cache for production
echo ""
echo "⚡ Re-caching for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 9. Set permissions
echo ""
echo "🔐 Setting storage permissions..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

echo ""
echo "✅ Update complete!"
echo "   $(date)"
