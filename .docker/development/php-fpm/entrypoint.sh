#!/bin/sh
set -e

# No need to change permissions, container runs as www user with correct UID/GID

# Clear configurations to avoid caching issues in development
echo "Clearing configurations..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Run the default command
if [ "$1" = "php-fpm" ]; then
    # php-fpm handles user switching internally based on config
    exec "$@"
else
    # For other commands (like artisan queue:work), run directly as current user
    exec "$@"
fi
