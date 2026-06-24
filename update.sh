#!/bin/bash
set -e

echo "=== dormed-shop Update ==="
echo ""

# 1. Git pull
echo "📦 Pulling latest code..."
git pull origin main

# 2. Composer install (--no-scripts to bypass Laravel 13 DevCommands bug)
echo ""
echo "📦 Installing/updating Composer dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Run post-install tasks manually
echo ""
echo "🔧 Running post-install tasks..."
php artisan package:discover --ansi 2>/dev/null || echo "   (package:discover warning ignored — known Laravel 13 bug)"
php artisan storage:link --force 2>/dev/null || true

# 3. Generate Wayfinder routes (via kernel API to avoid CLI DevCommands bug)
echo ""
echo "🗺️  Generating Wayfinder routes..."
php wayfinder.php

# 4. NPM install & build
echo ""
echo "📦 Installing NPM dependencies..."
npm install --no-audit --no-fund

echo ""
echo "🔨 Building frontend assets..."
VITE_NO_WAYFINDER=0 npm run build

# 5. Run migrations
echo ""
echo "🗄️  Running database migrations..."
php artisan migrate --force 2>/dev/null || php artisan migrate --force

# 6. Clear caches
echo ""
echo "🧹 Clearing caches..."
php artisan config:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true

# 7. Restart queue (if running)
echo ""
echo "🔄 Restarting queue workers..."
php artisan queue:restart 2>/dev/null || true

# 8. Re-cache for production
echo ""
echo "⚡ Re-caching for production..."
php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true
php artisan view:cache 2>/dev/null || true

# 9. Set permissions
echo ""
echo "🔐 Setting storage permissions..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

echo ""
echo "✅ Update complete!"
echo "   $(date)"
