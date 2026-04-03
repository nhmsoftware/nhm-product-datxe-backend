# ============================================================
# BASE STAGE — dùng chung cho dev và production
# ============================================================
FROM php:8.3-cli-alpine AS base

# Cài runtime libs (không phải build tools) → không bị xóa sau build
RUN apk add --no-cache \
        libpq \
        icu \
        oniguruma \
        libzip \
        bash

# Build extensions trong một layer, xóa build-deps ngay sau
RUN apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        postgresql-dev \
        icu-dev \
        oniguruma-dev \
        libzip-dev \
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        mbstring \
        intl \
        zip \
        opcache \
        pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps \
    && rm -rf /tmp/* /var/cache/apk/*

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# ============================================================
# DEV STAGE — chạy local, không cache artisan, dùng artisan serve
# ============================================================
FROM base AS dev

# Dev không cần optimize, để hot-reload thoải mái
ENV APP_ENV=local \
    APP_DEBUG=true

EXPOSE 8000

# Chạy composer install rồi mới serve
# (vendor được mount từ host nên cần install lại nếu chưa có)
CMD ["sh", "-c", "composer install --no-interaction && php artisan serve --host=0.0.0.0 --port=8000"]
