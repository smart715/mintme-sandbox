#!/bin/bash
dbname="ninjo"
offset="14800"

export APP_ENV=dev
export APP_SECRET=7fe58b36520fb4767584231b04836e13
export DATABASE_URL=mysql://mintme:sN09YUU3dX@127.0.0.1:3306/$dbname
export YOUTUBE_CLIENT=984627114486-t0iu4kij0spd19r6er8omo9jj0h1dpas.apps.googleusercontent.com
export FACEBOOK_CLIENT=243635433206441
export VIABTC_FORCE_WS_AUTH=0;
export VIABTC_OFFSET=$offset;
export RABBITMQ_URL=amqp://guest:guest@localhost:5672
export DEPOSIT_RPC=http://localhost:3000
export WITHDRAW_RPC=http://localhost:8743
export VIABTC_RPC=http://localhost:8080
export WEBSOCKET_URL=ws://10.81.142.6:8364

#php bin/console fos:user:promote wryqocfx@10mail.org ROLE_SUPER_ADMIN
php bin/console cache:clear --no-warmup
php bin/console cache:warmup

chown -R ninjo:www-data node_modules/ vendor/ templates/ translations/ src/ config/ public/ var/ assets/ bin/
chmod 775 var/log var/cache
