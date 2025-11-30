FROM php:8.1-apache

# Copy application files to the Apache document root
COPY . /var/www/html/

# Ensure www-data owns the files
RUN chown -R www-data:www-data /var/www/html

# Enable Apache rewrites (if needed)
RUN a2enmod rewrite

EXPOSE 80

CMD ["apache2-foreground"]
