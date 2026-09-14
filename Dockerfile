FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install curl gd xml

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Configure Apache to allow .htaccess overrides
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Set ownership and permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Create directories that need write access (if they don't exist)
RUN mkdir -p sessions videos channels \
    && chown -R www-data:www-data sessions videos channels \
    && chmod -R 775 sessions videos channels

# Set default memory limit
ENV PHP_MEMORY_LIMIT=512M

# Expose port 80
EXPOSE 80

# Use a shell to write the environment variable to php.ini at runtime
CMD ["sh", "-c", "echo memory_limit=$PHP_MEMORY_LIMIT > /usr/local/etc/php/conf.d/memory-limit.ini && apache2-foreground"]
