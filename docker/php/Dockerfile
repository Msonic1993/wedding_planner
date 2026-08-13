FROM composer:2 AS composer

FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
        curl \
        freetype \
        icu-libs \
        libjpeg-turbo \
        libpng \
        libzip \
        oniguruma \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        curl-dev \
        freetype-dev \
        icu-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libzip-dev \
        linux-headers \
        oniguruma-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        curl \
        gd \
        intl \
        opcache \
        pcntl \
        pdo_mysql \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps \
    && addgroup -g 1000 app \
    && adduser -D -u 1000 -G app app

COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-wedding-planet.ini

WORKDIR /var/www/html
USER app

CMD ["php-fpm"]
