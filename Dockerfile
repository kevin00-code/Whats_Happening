FROM php:8.2-apache

# Install SQLite (Required by your app)
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite

RUN a2enmod rewrite

# Copy everything from your repo
COPY . /var/www/html/

# Create missing folders and give Apache full ownership
RUN mkdir -p /var/www/html/includes /var/www/html/data /var/www/html/logs && \
    chown -R www-data:www-data /var/www/html/ && \
    chmod -R 775 /var/www/html/data /var/www/html/logs

WORKDIR /var/www/html/
