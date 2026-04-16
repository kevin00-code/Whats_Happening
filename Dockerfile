# 1. Start with the PHP + Apache base image
FROM php:8.2-apache

# 2. Install SQLite dependencies
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite

# 3. Enable Apache mod_rewrite for .htaccess security rules
RUN a2enmod rewrite

# 4. Copy your project files into the container
COPY . /var/www/html/

# 5. Create directories and fix permissions for automation
RUN mkdir -p /var/www/html/whats_happening/ && \
    chown -R www-data:www-data /var/www/html/ && \
    chmod -R 775 /var/www/html/data /var/www/html/logs
