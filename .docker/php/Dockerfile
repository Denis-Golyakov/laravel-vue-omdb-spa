FROM php:8.3-fpm-alpine

WORKDIR /var/www/html

# System dependencies
RUN apk add --no-cache \
    bash vim git curl zip unzip openssl coreutils \
    autoconf g++ make \
    libzip-dev oniguruma-dev sqlite-dev \
    nodejs pnpm

# Entrypoint
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# PHP Extensions
RUN docker-php-ext-install -j$(nproc) \
    pdo_sqlite \
    zip \
    mbstring \
    opcache

# Composer
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin \
    --filename=composer

# Cleanup
RUN rm -rf /tmp/pear

ENTRYPOINT ["/entrypoint.sh"]

CMD ["php-fpm"]