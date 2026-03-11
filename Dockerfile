# Use the official PHP + Apache image
FROM php:8.2-apache

# Enable Apache mod_rewrite (common for PHP apps)
RUN a2enmod rewrite

# Copy your files into the container
COPY . /var/www/html/

# Set permissions so the script can write to data/ and logs/
RUN chmod -R 755 /var/www/html/data /var/www/html/logs

# Tell the server to listen on port 80
EXPOSE 80
