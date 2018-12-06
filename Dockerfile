FROM php:7.2-fpm

RUN apt-get update && apt-get install -y \
    openssl \
    git \
    unzip \
    libzip-dev \
    wget \
    gnupg \
    iputils-ping \
    libpng-dev

RUN touch /usr/local/etc/php/php.ini
RUN echo 'memory_limit=512M' >> /usr/local/etc/php/php.ini

# NodeJs
RUN wget -qO- https://deb.nodesource.com/setup_10.x | bash -

RUN apt-get install -y nodejs
RUN apt-get install -y build-essential

# Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN docker-php-ext-install mysqli pdo pdo_mysql zip bcmath pcntl sockets gd

COPY . /var/www/html/panel

WORKDIR /var/www/html/panel

RUN npm i
RUN npm run dev

RUN echo 'pm = static' >> /usr/local/etc/php-fpm.d/www.conf
RUN echo 'pm.max_children = 1' >> /usr/local/etc/php-fpm.d/www.conf


