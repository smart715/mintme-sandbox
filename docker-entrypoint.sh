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

# Install npm deps
npm i
npm run dev

# Install composer deps
composer install

# Prepare database env
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate --allow-no-migration -n

# Starting internal services
php bin/console cron:start
nohup php bin/console rabbitmq:consumer payment &
nohup php bin/console rabbitmq:consumer deposit &
nohup php bin/console rabbitmq:consumer market &

# Fallback to original entrypoint
docker-php-entrypoint php-fpm
