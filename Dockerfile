FROM php:8.2-apache

# Set Apache document root to the root directory (where index.php is)
ENV APACHE_DOCUMENT_ROOT /var/www/html

# Update Apache configuration
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copy your application code
COPY . /var/www/html/
