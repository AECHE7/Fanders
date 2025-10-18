FROM php:8.1-cli

# Install system dependencies and PHP extensions needed for the app
RUN apt-get update \
    && apt-get install -y --no-install-recommends default-mysql-client libzip-dev unzip git zlib1g-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring zip xml \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer --version

WORKDIR /app

COPY . /app

# Keep container running to allow exec into it for running commands
CMD ["tail", "-f", "/dev/null"]
