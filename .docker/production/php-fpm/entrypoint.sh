#!/bin/sh
set -e

# Initialize storage directory if empty
# -----------------------------------------------------------
# If the storage directory is empty, copy the initial contents
# and set the correct permissions.
# This script runs as root to allow chown operations.
# -----------------------------------------------------------
if [ ! "$(ls -A /var/www/storage)" ]; then
  echo "Initializing storage directory..."
  cp -R /var/www/storage-init/. /var/www/storage
  chown -R www-data:www-data /var/www/storage
fi

# Remove storage-init directory
rm -rf /var/www/storage-init

# Run Laravel migrations as www-data
# -----------------------------------------------------------
# Ensure the database schema is up to date.
# -----------------------------------------------------------
gosu www-data php artisan migrate --force

# Clear and cache configurations as www-data
# -----------------------------------------------------------
# Improves performance by caching config and routes.
# -----------------------------------------------------------
gosu www-data php artisan config:cache
gosu www-data php artisan route:cache

# Switch to www-data user and run the default command
exec gosu www-data "$@"
