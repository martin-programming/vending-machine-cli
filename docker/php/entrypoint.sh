#!/bin/sh
set -e

if [ ! -f "vendor/autoload.php" ]; then
    echo "==> Installing Composer dependencies..."
    composer install --no-interaction --no-progress --optimize-autoloader
fi

if [ ! -f .env ]; then
    echo "==> Copying .env.example to .env..."
    cp .env.example .env
    php artisan key:generate --quiet
fi

exec "$@"
