#!/bin/bash

# Wait for database to be ready (if using local MongoDB)
# if [ "$DB_CONNECTION" = "mongodb" ] && [ "$DB_HOST" = "mongodb" ]; then
#   echo "Waiting for MongoDB to be ready..."
#   while ! nc -z mongodb 27017; do
#     sleep 1
#   done
#   echo "MongoDB is ready!"
# fi

# Run database migrations (if needed)
# php artisan migrate --force

# Clear and cache config
php artisan config:clear
php artisan config:cache

# Clear and cache routes
php artisan route:clear
php artisan route:cache

# Clear and cache views
php artisan view:clear
php artisan view:cache

# Start Apache
apache2-foreground