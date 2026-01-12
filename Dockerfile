FROM php:8.1-cli

WORKDIR /app

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends libssl-dev pkg-config libzip-dev git unzip \
    && docker-php-ext-install zip \
    && pecl install mongodb-1.21.0 \
    && docker-php-ext-enable mongodb \
    && rm -rf /var/lib/apt/lists/*

COPY . /app

RUN composer install

CMD ["./vendor/bin/phpunit", "tests"]
