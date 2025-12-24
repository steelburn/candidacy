# Document Parser Service
FROM candidacy-base:latest
COPY . /var/www/html
RUN cd /var/www/html && composer install --no-interaction --optimize-autoloader --no-dev --ignore-platform-reqs
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
