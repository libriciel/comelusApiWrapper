ARG PHP_VERSION=8.3

FROM hubdocker.libriciel.fr/php:${PHP_VERSION}-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/*

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug
ENV XDEBUG_MODE=coverage

COPY --from=hubdocker.libriciel.fr/composer:2 /usr/bin/composer /usr/bin/composer

ARG UID=1000
ARG GID=1000
RUN groupadd --gid ${GID} app \
    && useradd --uid ${UID} --gid ${GID} --create-home app \
    && mkdir /app \
    && chown app:app /app

USER app
WORKDIR /app

COPY --chown=app:app . /app
RUN composer install --no-interaction --prefer-dist
