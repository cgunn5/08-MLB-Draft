#!/usr/bin/env bash
#
# Laravel Forge deploy script for MLB Draft.
# Paste this into Forge → Your Site → Deploy Script (replace the default).
#
set -e

cd "$FORGE_SITE_PATH"

git pull origin "$FORGE_SITE_BRANCH"

$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

npm ci
npm run build

php artisan migrate --force
php artisan optimize:clear
php artisan app:forge-persistence-doctor --ansi

echo "Deploy complete. Persistent data lives under storage/app/ — never in database/."
