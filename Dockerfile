FROM php:8.4-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends libicu-dev=76.1-4  \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install intl

COPY --from=composer /usr/bin/composer /usr/local/bin/composer

RUN pecl install xdebug && docker-php-ext-enable xdebug

WORKDIR /app
