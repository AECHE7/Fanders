FROM php:8.1-apache

# Install system dependencies and PHP extensions needed for the app
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        default-mysql-client \
        libpq-dev \
        libzip-dev \
        unzip \
        git \
        zlib1g-dev \
        libonig-dev \
        libxml2-dev \
        netcat-openbsd \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql mbstring zip xml \
    && rm -rf /var/lib/apt/lists/*

# Enable apache mods and set document root
RUN a2enmod rewrite headers
ENV APACHE_DOCUMENT_ROOT=/app/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Suppress ServerName warning by setting a default
RUN echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer --version

WORKDIR /app

# Copy composer files first to leverage Docker cache
COPY composer.json composer.lock* /app/

# Install PHP dependencies
RUN composer install --prefer-dist --no-dev --no-interaction --optimize-autoloader --no-progress

# Copy rest of the app
COPY . /app

# Ensure permissions for web server
RUN chown -R www-data:www-data /app

# Add entrypoint that waits for DB and can run DB init scripts when requested
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose default HTTP port
EXPOSE 80

# Start Apache in foreground (entrypoint will run first)
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
