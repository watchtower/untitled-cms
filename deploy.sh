#!/bin/bash

# Deploy Script for Laravel 12 + MongoDB CMS
# Usage: ./deploy.sh

set -e

echo "🚀 Starting deployment..."

# 1. Pull latest changes
git pull origin main

# 2. Install PHP dependencies
composer install --no-dev --optimize-autoloader

# 3. Install Node dependencies & Build assets
npm ci
npm run build

# 4. Cache Config & Routes
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Run Migrations (if any - generic)
# php artisan migrate --force 
# Note: MongoDB usually doesn't need migrations in the same way, but good to run if you have any hybrid migrations.

# 6. Restart Queue (if using queues)
# php artisan queue:restart

echo "✅ Deployment complete!"
