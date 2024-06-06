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

# Need a little wait until BTC service is fully ready
sleep 10

if test ! -f ".env"; then
    cp .env.dist .env
fi

# Install composer deps
composer install --no-scripts

# Install npm deps
npm i

if [ "$APP_ENV" == "prod" ]; then
  npm run prod;
else
  npm run dev;
fi

# Set permissions
chown -R :www-data /var/www/html/panel/
chmod -R g+w /var/www/html/panel/var/
chmod -R g+s /var/www/html/panel/

# Prepare database env
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate --allow-no-migration -n

# generate translations
php bin/console app:load-translations-ui

# load assets
composer run post-install-cmd

# Starting internal services
echo 'Starting crons...'
php bin/console cron:start --blocking &
echo 'Starting payment consumer...'
php bin/console rabbitmq:consumer payment &
echo 'Starting email consumer...'
php bin/console rabbitmq:consumer email &
echo 'Starting deposit consumer...'
php bin/console rabbitmq:consumer deposit &
echo 'Starting market consumer...'
php bin/console rabbitmq:consumer market &
echo 'Starting token consumer...'
php bin/console rabbitmq:consumer deploy &
echo 'Starting update consumer...'
php bin/console rabbitmq:consumer contract_update &

# Fallback to original entrypoint
docker-php-entrypoint php-fpm
