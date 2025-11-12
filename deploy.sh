#!/bin/bash

set -e

echo "========================================"
echo "Laravel Lightsail Deployment Script"
echo "========================================"

DEPLOY_PATH="${DEPLOY_PATH:-/home/bitnami/htdocs/GradWorks}"
MAINTENANCE_SECRET="${MAINTENANCE_SECRET:-}"

echo "Deploy path: ${DEPLOY_PATH}"
cd "${DEPLOY_PATH}"

echo ""
echo "===== [1/12] Enabling maintenance mode ====="
if [ -n "${MAINTENANCE_SECRET}" ]; then
    php artisan down --retry=60 --secret="${MAINTENANCE_SECRET}"
    echo "Maintenance mode enabled with secret: ${MAINTENANCE_SECRET}"
    echo "Access URL: ${APP_URL}/${MAINTENANCE_SECRET}"
else
    php artisan down --retry=60 || true
    echo "Maintenance mode enabled"
fi

echo ""
echo "===== [2/12] Pulling latest code ====="
git fetch origin main
git reset --hard origin/main
echo "Latest code pulled from main branch"

echo ""
echo "===== [3/12] Installing Composer dependencies ====="
composer install --optimize-autoloader --no-dev --no-interaction --prefer-dist
echo "Composer dependencies installed"

echo ""
echo "===== [4/12] Checking .env file ====="
if [ ! -f .env ]; then
    echo ".env file not found. Creating from .env.example"
    cp .env.example .env
    php artisan key:generate --force
else
    echo ".env file exists"
fi

echo ""
echo "===== [5/12] Clearing configuration cache ====="
php artisan config:clear
php artisan cache:clear
php artisan route:clear
echo "Configuration cache cleared"

echo ""
echo "===== [6/12] Running database migrations ====="
php artisan migrate --force
echo "Database migrations completed"

echo ""
echo "===== [7/12] Caching views ====="
php artisan view:clear
php artisan view:cache
echo "Views cached"

echo ""
echo "===== [8/12] Creating storage link ====="
php artisan storage:link || echo "Storage link already exists"

echo ""
echo "===== [9/12] Setting file permissions ====="
sudo chown -R bitnami:daemon storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
echo "File permissions set"

echo ""
echo "===== [10/12] Optimizing application ====="
php artisan optimize
echo "Application optimized"

echo ""
echo "===== [11/12] Disabling maintenance mode ====="
php artisan up
echo "Maintenance mode disabled"

echo ""
echo "===== [12/12] Restarting Apache ====="
sudo /opt/bitnami/ctlscript.sh restart apache
echo "Apache restarted"

echo ""
echo "========================================"
echo "✅ Deployment completed successfully!"
echo "========================================"
echo "Deployed at: $(date)"
echo "Branch: main"
echo "Commit: $(git rev-parse --short HEAD)"
echo "========================================"
