FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    libpng-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    ffmpeg \
    cron \
    nano \
    vim \
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
RUN echo "upload_max_filesize=20M" > /usr/local/etc/php/conf.d/custom-php.ini && \
    echo "post_max_size=25M" >> /usr/local/etc/php/conf.d/custom-php.ini && \
    echo "max_execution_time=3600" >> /usr/local/etc/php/conf.d/custom-php.ini && \
    echo "max_input_time=3600" >> /usr/local/etc/php/conf.d/custom-php.ini && \
    echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/custom-php.ini && \
    echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/custom-php.ini && \
    echo "opcache.interned_strings_buffer=8" >> /usr/local/etc/php/conf.d/custom-php.ini && \
    echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/custom-php.ini && \
    echo "opcache.revalidate_freq=2" >> /usr/local/etc/php/conf.d/custom-php.ini && \
    echo "opcache.fast_shutdown=1" >> /usr/local/etc/php/conf.d/custom-php.ini

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Install the container entrypoint (starts the optional prewarmer cron, then Apache)
RUN cp /var/www/html/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /usr/local/bin/docker-entrypoint.sh

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

# Start via the entrypoint: applies the memory limit, optionally launches the
# prewarmer cron (PREWARM_ENABLED=true), then runs Apache in the foreground.
CMD ["/usr/local/bin/docker-entrypoint.sh"]
