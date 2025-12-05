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

# Set up cron job for Laravel scheduler
# -----------------------------------------------------------
# Create a crontab entry that runs Laravel's scheduler every minute
# -----------------------------------------------------------
echo "Setting up Laravel scheduler cron job..."
echo "* * * * * cd /var/www && gosu www-data php artisan schedule:run >> /dev/null 2>&1" > /etc/cron.d/laravel-scheduler

# Give execution rights on the cron job
chmod 0644 /etc/cron.d/laravel-scheduler

# Apply cron job
crontab /etc/cron.d/laravel-scheduler

# Create the log file to be able to run tail
touch /var/log/cron.log

# Start cron in the background
echo "Starting cron daemon..."
cron

# Run the command passed as arguments (queue worker)
# -----------------------------------------------------------
# This will be the queue:work command specified in docker-compose
# We use gosu to run it as www-data for security
# -----------------------------------------------------------
echo "Starting queue worker with command: $@"
exec gosu www-data $@
