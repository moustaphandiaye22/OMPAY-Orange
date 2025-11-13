#!/bin/bash

# Wait for database to be ready (if using PostgreSQL)
if [ "$DB_CONNECTION" = "pgsql" ]; then
  echo "Waiting for PostgreSQL to be ready..."
  while ! nc -z $DB_HOST $DB_PORT; do
    sleep 1
  done
  echo "PostgreSQL is ready!"
fi

# Run database migrations (if needed)
php artisan migrate --force

# Clear config (do not cache in production to allow env vars to take effect)
php artisan config:clear

# Clear and cache routes
php artisan route:clear
php artisan route:cache

# Clear and cache views
php artisan view:clear
php artisan view:cache

# Clear cached API documentation and regenerate
rm -rf storage/api-docs/*
php artisan l5-swagger:generate

# Start Apache
apache2-foreground