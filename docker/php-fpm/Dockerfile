FROM composer:latest AS composer

FROM phpdockerio/php:8.3-fpm AS builder
WORKDIR "/application"

ENV TZ=Europe/Berlin
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN apt-get update; \
    apt-get -y --no-install-recommends install \
        git \
        php8.3-gd \
        php8.3-xml \
        php8.3-zip \
        php8.3-mbstring \
        php8.3-intl \
        php8.3-soap \
        php8.3-curl \
        php8.3-bcmath \
        php8.3-amqp \
        php8.3-memcached \
        php8.3-imagick \
        php8.3-pgsql \
        php8.3-mysql \
        php8.3-mongodb; \
    apt-get clean; \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

# Install Composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

FROM builder AS dev
RUN apt-get update; \
    apt-get -y --no-install-recommends install \
        php8.3-xdebug; \
    apt-get clean; \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

FROM builder AS prod
RUN apt-get update; \
    apt-get -y --no-install-recommends install \
        php8.3-opcache; \
    apt-get clean; \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*


