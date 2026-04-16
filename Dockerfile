# 1. Copy your files
COPY . /var/www/html/

# 2. Ensure the directory exists, then set permissions
RUN mkdir -p /var/www/html/whats_happening/ && \
    chown -R www-data:www-data /var/www/html/ && \
    chmod -R 775 /var/www/html/
