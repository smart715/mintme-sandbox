#!/usr/bin/env bash

until nc -z -v -w30 db 3306
do
    echo "Waiting for database connection..."
    sleep 5
done

until nc -z -v -w30 rabbitmq 15672
do
    echo "Waiting for amqp connection..."
    sleep 5
done

until nc -z -v -w30 btc 8080
do
    echo "Waiting for viabtc connection..."
    sleep 5
done

if test ! -f ".env"; then
    cp .env.dist .env
fi

# Install npm deps
npm i
npm run dev

# Install composer deps
composer install

# Prepare database env
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate --allow-no-migration -n

# Starting internal services
echo 'Starting crons...'
php bin/console cron:start
echo 'Starting payment consumer...'
nohup php bin/console rabbitmq:consumer payment &
echo 'Starting deposit consumer...'
nohup php bin/console rabbitmq:consumer deposit &
echo 'Starting market consumer...'
nohup php bin/console rabbitmq:consumer market &

# Fallback to original entrypoint
docker-php-entrypoint php-fpm
