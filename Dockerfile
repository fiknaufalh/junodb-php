# Use the official PHP 8.1 image
FROM php:8.1-fpm-bullseye

# Install required dependencies
RUN apt-get update && apt-get install -y \
    libmemcached-dev \
    zlib1g-dev \
    && pecl install memcached-3.1.5 \
    && docker-php-ext-enable memcached \
    && docker-php-ext-install sockets

# Set working directory
WORKDIR /var/www/html

# Copy source code to working directory
COPY . /var/www/html
