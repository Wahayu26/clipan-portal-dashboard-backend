FROM php:8.3-cli-alpine

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

WORKDIR /var/www/html

# Copy application files
COPY . .

# Set permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

# Run lightweight PHP 8.3 server routing through public/index.php
CMD ["php", "-S", "0.0.0.0:80", "-t", "public", "public/index.php"]
