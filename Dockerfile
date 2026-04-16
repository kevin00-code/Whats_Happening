FROM php:8.2-apache

# Install SQLite
RUN apt-get update && apt-get install -y libsqlite3-dev && docker-php-ext-install pdo_sqlite

# Enable Apache Rewrite
RUN a2enmod rewrite

# Copy your files
COPY . /var/www/html/

# Create the folders and fix permissions
# This is the "Magic Line" that stops your specific deployment error
RUN mkdir -p /var/www/html/includes /var/www/html/data /var/www/html/logs /var/www/html/api && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 775 /var/www/html/data /var/www/html/logs

EXPOSE 80
