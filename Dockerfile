FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    libpng-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install curl gd xml zip opcache

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Configure Apache to allow .htaccess overrides
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Set Apache timeout for long-running scripts
RUN echo "Timeout 3600" >> /etc/apache2/apache2.conf

# Create custom PHP configuration for performance, timeouts, and uploads
RUN cat << 'INICONTENT' > /usr/local/etc/php/conf.d/custom-php.ini
upload_max_filesize=20M
post_max_size=25M
max_execution_time=3600
max_input_time=3600
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
INICONTENT

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
ENV PHP_MEMORY_LIMIT=1024M

# Expose port 80
EXPOSE 80

# Configure PHP runtime memory limit and start Apache
CMD ["sh", "-c", "echo \"memory_limit=${PHP_MEMORY_LIMIT}\" > /usr/local/etc/php/conf.d/memory-limit.ini && apache2-foreground"]
