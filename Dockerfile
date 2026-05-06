FROM php:8.1-fpm-bookworm

WORKDIR /var/www/html

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    PHP_OPCACHE_VALIDATE_TIMESTAMPS=0

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        git \
        unzip \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        gd \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo_mysql \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --no-autoloader

COPY . .
COPY docker/php/local.ini /usr/local/etc/php/conf.d/zz-zxsms-local.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/zz-zxsms-opcache.ini
COPY docker/php/entrypoint.sh /usr/local/bin/zxsms-entrypoint

RUN composer dump-autoload --optimize --no-dev \
    && mkdir -p \
        storage/app/public \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chmod +x /usr/local/bin/zxsms-entrypoint \
    && chown -R www-data:www-data storage bootstrap/cache

ENTRYPOINT ["zxsms-entrypoint"]
CMD ["php-fpm"]
