FROM php:8.5-fpm-bookworm AS php-base

ARG APP_UID=1000
ARG APP_GID=1000

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/tmp/composer \
    PATH="/var/www/html/vendor/bin:${PATH}"

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        git \
        libfcgi-bin \
        libicu-dev \
        libonig-dev \
        libpq-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo_pgsql \
        posix \
        sockets \
        zip \
    && pecl install redis pcov \
    && docker-php-ext-enable redis \
    && rm -rf /tmp/pear /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-app.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/20-opcache.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/zz-app.conf

RUN groupmod --gid "${APP_GID}" www-data \
    && usermod --uid "${APP_UID}" --gid "${APP_GID}" --home /var/www --shell /bin/bash www-data \
    && mkdir -p /var/www/html /var/www/.composer \
    && chown -R www-data:www-data /var/www

WORKDIR /var/www/html
USER www-data

FROM php-base AS development

ENV APP_ENV=local
COPY docker/php/development.ini /usr/local/etc/php/conf.d/25-development.ini
CMD ["php-fpm", "--nodaemonize"]

FROM php-base AS test

ENV APP_ENV=testing
COPY docker/php/pcov.ini /usr/local/etc/php/conf.d/30-pcov.ini
COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-progress --no-scripts
COPY --chown=www-data:www-data . .
RUN touch .env && composer run-script post-autoload-dump --no-interaction
CMD ["php", "artisan", "test"]

FROM php-base AS vendor-production

COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --no-progress \
    --classmap-authoritative \
    --no-scripts

FROM php-base AS production

ENV APP_ENV=production \
    APP_DEBUG=false
COPY --chown=www-data:www-data . .
COPY --from=vendor-production --chown=www-data:www-data /var/www/html/vendor ./vendor
RUN composer run-script post-autoload-dump --no-interaction \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs \
    && chmod -R u+rwX storage bootstrap/cache
CMD ["php-fpm", "--nodaemonize"]
