FROM php:8.2-apache

# Install SQLite support if your app uses it
RUN apt-get update && apt-get install -y libsqlite3-dev && docker-php-ext-install pdo_sqlite

# Copy your files to the server
COPY . /var/www/html/

# CRITICAL: Give Apache ownership and write permissions
RUN chown -R www-data:www-data /var/www/html/ && \
    chmod -R 775 /var/www/html/whats_happening/

EXPOSE 80
