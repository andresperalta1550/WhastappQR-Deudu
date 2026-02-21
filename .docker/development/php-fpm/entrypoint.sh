#!/bin/sh
set -e

# Running as root to fix permissions
USER_ID=${UID:-1000}
GROUP_ID=${GID:-1000}

echo "Fixing file permissions with UID=${USER_ID} and GID=${GROUP_ID}..."
chown -R ${USER_ID}:${GROUP_ID} /var/www || echo "Some files could not be changed"

# Install Composer dependencies if vendor directory is missing
# This is necessary because the volume mount overwrites /var/www at runtime
if [ ! -f /var/www/vendor/autoload.php ]; then
    echo "vendor/autoload.php not found. Running composer install..."
    cd /var/www && composer install --no-interaction --no-progress --prefer-dist
fi

# Clear configurations to avoid caching issues in development
echo "Clearing configurations..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Run the default command as www user (not root)
exec "$@"
