# Use official PHP 8.2 image with Apache
FROM php:8.2-apache

# Install and enable mysqli extension
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Enable Apache mod_rewrite (if you use any .htaccess or clean URLs)
RUN a2enmod rewrite

# Copy all project files into Apache’s webroot
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html

# Ensure Apache (www-data) owns everything so PHP can write uploads/
RUN chown -R www-data:www-data /var/www/html

# Expose port 80 (Apache)
EXPOSE 80
