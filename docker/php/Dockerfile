FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    libzip-dev \
    zip \
    unzip \
    libxml2-dev \
    git

RUN docker-php-ext-install pdo_mysql zip dom xml

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www