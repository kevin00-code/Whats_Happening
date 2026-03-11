FROM php:8.2-apache
COPY . /var/www/html/
# Grant permissions for the web server to read all files and write to specific folders
RUN chown -R www-data:www-data /var/www/html/ && chmod -R 755 /var/www/html/
EXPOSE 80
