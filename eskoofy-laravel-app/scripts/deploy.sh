#!/usr/bin/env bash
# Production deployment script for the School Management System.
# Run on the production server after pulling the latest code.
# Usage: bash scripts/deploy.sh

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BACKEND="$ROOT/backend"

cd "$BACKEND"

echo "==> Putting app in maintenance mode"
php artisan down --retry=60 --secret="school-deploy-$(date +%s)"

echo "==> Pulling latest code (assumes you already did git pull)"
# git pull origin main

echo "==> Installing composer dependencies (no dev)"
composer install --optimize-autoloader --no-dev --no-interaction --prefer-dist

echo "==> Installing npm dependencies and building assets"
npm ci --no-audit --no-fund
npm run build

echo "==> Running migrations"
php artisan migrate --force --no-interaction

echo "==> Seeding only the role/permission seeder (idempotent)"
php artisan db:seed --class=RolePermissionSeeder --force
php artisan db:seed --class=AdminUserSeeder --force

echo "==> Clearing and caching config, routes, views"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "==> Optimizing autoloader"
composer dump-autoload --optimize --no-dev

echo "==> Linking storage"
php artisan storage:link || true

echo "==> Restarting queue workers (if any)"
php artisan queue:restart || true

echo "==> Bringing app back online"
php artisan up

echo
echo "==> Deployment complete. Verify with: php artisan about"
