FROM php:8.2-apache
COPY . /var/www/html/
RUN chmod -R 755 /var/www/html/data /var/www/html/logs
