# pulling base images
FROM node:16.17.0-alpine3.16 AS node
FROM composer:2.5.1 AS composer
FROM php:7.4-fpm

# resolves /tmp security issue in latest debian version which prevents to update packages
RUN chmod 1777 /tmp

# install requirements
RUN apt-get update && apt-get install --no-install-recommends -y \
    libicu-dev \
    libpng-dev \
    libgmp-dev \
    libzip-dev \
    netcat \
    iproute2 \
    openssl \
    git \
    unzip \
    libzip-dev \
    wget \
    gnupg \
    iputils-ping \
    libfontconfig \
    && rm -rf /var/lib/apt/lists/*

# configure docker extensions
RUN docker-php-ext-configure intl \
    && docker-php-ext-install intl mysqli pdo_mysql zip bcmath pcntl sockets gd gmp

# installing apcu and xdebug
RUN yes | pecl install xdebug-2.9.6 \
    && echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_enable=true" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.idekey=Docker" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_host=$(ip route show | awk '/default/ {print $3}')" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_autostart=true" >> /usr/local/etc/php/conf.d/xdebug.ini
RUN yes | pecl install apcu

# set up node and npm
COPY --from=node /usr/lib /usr/lib
COPY --from=node /usr/local/share /usr/local/share
COPY --from=node /usr/local/lib /usr/local/lib
COPY --from=node /usr/local/include /usr/local/include
COPY --from=node /usr/local/bin /usr/local/bin

# musl to make node alpine work in ubuntu base
RUN curl https://musl.libc.org/releases/musl-1.2.2.tar.gz | tar -xzC /tmp/ &&\
    cd /tmp/musl-1.2.2 &&\
    ./configure &&\
    make &&\
    make install &&\
    rm -rf /tmp/musl-1.2.2

# set up composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

# configure php
RUN touch /usr/local/etc/php/php.ini
RUN echo 'memory_limit=-1' >> /usr/local/etc/php/php.ini \
    && echo 'extension=apcu.so' >> /usr/local/etc/php/php.ini
RUN sed -i 's/max_children = 5/max_children = 20/g' /usr/local/etc/php-fpm.d/www.conf
RUN echo "pm.max_requests = 200" >> /usr/local/etc/php-fpm.d/www.conf

COPY docker-entrypoint.sh /usr/local/bin/app-docker-entrypoint.sh
RUN chmod 755 /usr/local/bin/app-docker-entrypoint.sh
WORKDIR /var/www/html/panel
ENTRYPOINT ["app-docker-entrypoint.sh"]
