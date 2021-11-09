FROM php:7.4-fpm

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
    netcat \
    iproute2 \
    libicu-dev \
    libgmp-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl \
    && docker-php-ext-install gmp

RUN yes | pecl install xdebug-2.9.6 \
    && echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_enable=true" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.idekey=Docker" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_host=$(ip route show | awk '/default/ {print $3}')" >> /usr/local/etc/php/conf.d/xdebug.ini

RUN yes | pecl install apcu

RUN touch /usr/local/etc/php/php.ini

RUN echo 'memory_limit=-1' >> /usr/local/etc/php/php.ini \
    && echo 'extension=apcu.so' >> /usr/local/etc/php/php.ini \
    && echo 'extension=intl' >> /usr/local/etc/php/php.ini

# NodeJs
RUN printf "Package: nodejs\nPin: origin deb.nodesource.com\nPin-Priority: 1000\n" > /etc/apt/preferences.d/nodesource
RUN rm -rf /var/lib/apt/lists/ && wget -qO- https://deb.nodesource.com/setup_10.x | bash -

RUN apt-get install -y nodejs
RUN apt-get install -y build-essential
RUN npm install npm@6.9.0 -g
# Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer --version=1.9.3

RUN docker-php-ext-install mysqli pdo pdo_mysql zip bcmath pcntl sockets gd

COPY docker-entrypoint.sh /usr/local/bin/app-docker-entrypoint.sh

RUN chmod 755 /usr/local/bin/app-docker-entrypoint.sh

WORKDIR /var/www/html/panel

ENTRYPOINT ["app-docker-entrypoint.sh"]
