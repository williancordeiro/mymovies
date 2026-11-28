FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    libzip-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    zip \
    unzip \
    libxml2-dev \
    git

RUN docker-php-ext-configure gd --with-jpeg && \
    docker-php-ext-install pdo_mysql zip dom xml gd
RUN mkdir -p /var/www/public/assets/uploads && \
    chown -R www-data:www-data /var/www/public/assets/uploads

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
