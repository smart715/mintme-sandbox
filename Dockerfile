FROM php:7.2-fpm

RUN apt-get update && apt-get install -y \
    openssl \
    git \
    unzip \
    libzip-dev \
    wget \
    gnupg \
    iputils-ping \
    libpng-dev \
    libfontconfig \
    netcat

RUN yes | pecl install xdebug \
    && echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_enable=on" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_autostart=off" >> /usr/local/etc/php/conf.d/xdebug.ini

RUN touch /usr/local/etc/php/php.ini
RUN echo 'memory_limit=-1' >> /usr/local/etc/php/php.ini

# NodeJs
RUN rm -rf /var/lib/apt/lists/ && wget -qO- https://deb.nodesource.com/setup_10.x | bash -

RUN apt-get install -y nodejs
RUN apt-get install -y build-essential

# Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN docker-php-ext-install mysqli pdo pdo_mysql zip bcmath pcntl sockets gd

COPY docker-entrypoint.sh /usr/local/bin/app-docker-entrypoint.sh

RUN chmod 755 /usr/local/bin/app-docker-entrypoint.sh

WORKDIR /var/www/html/panel

ENTRYPOINT ["app-docker-entrypoint.sh"]
