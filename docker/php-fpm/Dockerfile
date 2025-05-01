FROM composer:latest AS composer

FROM phpdockerio/php:8.4-fpm AS builder
WORKDIR "/application"

ENV TZ=Europe/Berlin
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN apt-get update; \
    apt-get -y --no-install-recommends install \
        git \
        php8.4-gd \
        php8.4-xml \
        php8.4-zip \
        php8.4-mbstring \
        php8.4-intl \
        php8.4-soap \
        php8.4-curl \
        php8.4-bcmath \
        php8.4-amqp \
        php8.4-memcached \
        php8.4-imagick \
        php8.4-pgsql \
        php8.4-mysql \
        php8.4-mongodb; \
    apt-get clean; \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

# Install Composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

FROM builder AS dev
RUN apt-get update; \
    apt-get -y --no-install-recommends install \
        php8.4-xdebug; \
    apt-get clean; \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

FROM builder AS prod
RUN apt-get update; \
    apt-get -y --no-install-recommends install \
        php8.4-opcache; \
    apt-get clean; \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*


