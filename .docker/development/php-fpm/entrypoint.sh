#!/bin/sh
set -e

# Running as root to fix permissions
USER_ID=${UID:-1000}
GROUP_ID=${GID:-1000}

echo "Fixing file permissions with UID=${USER_ID} and GID=${GROUP_ID}..."
chown -R ${USER_ID}:${GROUP_ID} /var/www || echo "Some files could not be changed"

# Install composer dependencies as www user (required for development/staging)
echo "Installing composer dependencies..."
gosu www composer install --optimize-autoloader --no-interaction --no-progress --prefer-dist

# Clear configurations to avoid caching issues in development
echo "Clearing configurations..."
gosu www php artisan config:clear
gosu www php artisan route:clear
gosu www php artisan view:clear

# Run the default command as www user (not root)
exec gosu www "$@"
