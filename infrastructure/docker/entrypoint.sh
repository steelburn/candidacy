#!/bin/bash
set -e

echo "Starting container entrypoint..."

# Generate JWT secret if not provided
if [ -z "$JWT_SECRET" ]; then
    echo "JWT_SECRET not set, generating one..."
    JWT_SECRET=$(php artisan jwt:secret --force --no-interaction 2>/dev/null || echo "fallback_secret_$(openssl rand -hex 32)")
    export JWT_SECRET
    echo "JWT_SECRET generated"
else
    echo "JWT_SECRET already set"
fi

# Ensure storage and bootstrap/cache directories have correct permissions
echo "Setting permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

# Execute the default command
echo "Executing command: $@"
exec "$@"
