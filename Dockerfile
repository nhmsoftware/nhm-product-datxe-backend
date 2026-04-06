# =============================================================================
# Dockerfile — Local development only
# Image nhỏ nhất để chạy Laravel trên local
# Source code bind-mount từ host → hot-reload, không rebuild khi sửa code
# =============================================================================

FROM php:8.3-cli-alpine

# Runtime libs
RUN apk add --no-cache \
        libpq \
        icu-libs \
        oniguruma \
        libzip \
        bash

# PHP extensions — xóa build-deps ngay trong cùng layer
RUN apk add --no-cache --virtual .build-deps \
        ${PHPIZE_DEPS} \
        linux-headers \
        postgresql-dev \
        icu-dev \
        oniguruma-dev \
        libzip-dev \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        mbstring \
        intl \
        zip \
        opcache \
        pcntl \
        sockets \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del --purge .build-deps \
    && rm -rf /tmp/pear /tmp/* /var/cache/apk/*

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

EXPOSE 8000

# composer install chạy mỗi lần container start
# vendor nằm trong named volume nên chỉ install lại khi composer.lock thay đổi
CMD ["sh", "-c", "composer install --no-interaction && php artisan serve --host=0.0.0.0 --port=8000"]
