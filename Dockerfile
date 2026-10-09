FROM ghcr.io/roadrunner-server/roadrunner:2025.1.15 AS roadrunner

FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libpq-dev \
    librabbitmq-dev \
    librdkafka-dev \
    libicu-dev \
    libzip-dev \
    libonig-dev \
    && docker-php-ext-install \
        pdo_pgsql \
        intl \
        zip \
        mbstring \
        opcache \
        sockets \
    && pecl install amqp rdkafka redis \
    && docker-php-ext-enable amqp rdkafka redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=roadrunner /usr/bin/rr /usr/local/bin/rr

WORKDIR /var/www/html
