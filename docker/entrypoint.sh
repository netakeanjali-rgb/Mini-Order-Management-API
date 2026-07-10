#!/bin/sh
set -e

cd /var/www

if [ ! -f "vendor/autoload.php" ]; then
    composer install --no-interaction --prefer-dist
fi

if [ ! -f ".env" ]; then
    cp .env.example .env
    php artisan key:generate --force
    php artisan jwt:secret --force
fi

php artisan config:clear

until php artisan migrate --force 2>/dev/null; do
    echo "Waiting for database..."
    sleep 2
done

exec "$@"
