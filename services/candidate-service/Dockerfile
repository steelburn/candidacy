# Candidate Service - uses base Laravel image
FROM candidacy-base:latest

# Copy application files
COPY . /var/www/html

# Install PHP extensions required for Octane/RoadRunner
RUN docker-php-ext-install sockets pcntl

# Install PHP dependencies
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Ensure RoadRunner binary is installed (will fail if octane not in composer.json yet, so we do it conditionally or after)
# For now, we just ensure extensions are there.

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Start Application using Octane
CMD ["php", "artisan", "octane:start", "--server=roadrunner", "--host=0.0.0.0", "--port=8080"]
