#!/bin/bash
BRANCH_IP=""
MKWK106_IP="37.48.76.140"
exec_command=${@:1}

#pwdlvl3=$(echo $PWD | awk -F"/" '{print $4}')
#if ! [ $(echo $PWD | awk -F"/" '{print $2$3}') == 'homewww' ] || ! [ $pwdlvl3 ] || ! [ -f  $PWD/bin/console ]; then
#	echo "Seems you are not at the root of any valid branch"
#	exit 1
#fi 

BRANCH_NAME=$(echo $PWD|awk -F"/" '{print $4}')
BRANCH_DB=sb_rhcamilex
BRANCH_IP=$(sudo --user=mintme ssh -p2279 mintme@$MKWK106_IP "sudo lxc list -c4 --format csv branch-$BRANCH_NAME | cut -d' ' -f1")
if [ $BRANCH_IP ]; then
   withdraw_port=$((8745 + $(echo "$BRANCH_IP" | cut -d'.' -f4)))
   export APP_ENV=prod
   export APP_SECRET=7fe58b36520fb4767584231b04836e13
   export DATABASE_URL=mysql://mintme:sN09YUU3dX@10.81.143.1:3306/$BRANCH_DB
   export MAILER_TRANSPORT=smtp
   export MAILER_HOST=mail.abchosting.org
   export MAILER_USER=mintme@mintme.abchosting.org
   export MAILER_PORT=587
   export MAILER_ENCRYPTION=tls
   export MAILER_AUTH_MODE=login
   export MAILER_PASSWORD=92HGRk5h
#   export GOOGLE_RECAPTCHA_SITE_KEY=6LduKI0UAAAAAD5d5orcDi1hQWm-TuZ-QF_l2Ks7
   export GOOGLE_RECAPTCHA_SITE_KEY=6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI
   export YOUTUBE_CLIENT=616023022148-v4dh8b26ugmn351udmgmt4ntab62437c.apps.googleusercontent.com
   export FACEBOOK_CLIENT=308376513111821
   export RABBITMQ_URL=amqp://guest:guest@$BRANCH_IP:5672;
   export VIABTC_FORCE_WS_AUTH=true
   export VIABTC_OFFSET=0
   export DEPOSIT_RPC=http://$BRANCH_IP:3000
   export WITHDRAW_RPC=http://localhost:$withdraw_port
   export VIABTC_RPC=http://$BRANCH_IP:8080
   export WEBSOCKET_URL=ws://$BRANCH_IP:8364
   export COINIFY_SHARED_SECRET=458e14ea-212a-4658-af0f-f580863ac126
   export TWITTER_API_KEY=m6Clz3zmCTK16viW5b3Z4s8gS
   export TWITTER_API_SECRET=b7jTvT4p5Y4C0hizSFydozV2mPujdcdNKD5W9StsR3McjbVihg
   export TWITTER_API_BEARER_TOKEN=AAAAAAAAAAAAAAAAAAAAALgILQEAAAAAUsI5eF194mctK2NjAfz5t4u3a4I%3DjorLrk8sPEOc0pjAs6QeMOdrp3BLj4IIsT0p6SGMlq8WjVEyit
   export MERCURE_PUBLISH_URL=http://$BRANCH_IP:3011/.well-known/mercure
   export MERCURE_JWT_TOKEN=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtZXJjdXJlIjp7InB1Ymxpc2giOltdfX0.NsZebnlGaA468bjcrqOdh80o6EDHlyIwReB2-UqsygI
   export DISCORD_BOT_TOKEN=ODI5NTIzOTUxNDc0MTgwMTQ3.YG5YYQ.fsIAt47EyrkR77L5oXLB3jZNpLs
   export DISCORD_CLIENT_ID=829523951474180147
   export DISCORD_CLIENT_SECRET=lXnpqYf7kFB3ySucI9GHyJe52dVujifH
   export DISCORD_PUBLIC_KEY=3bba5001355af279df95bb235d12df88eccf7d261bd7836c0f99e3a7f5c240f3

   echo "Environment variables set for new test environment"
else
   export APP_ENV=prod
   export APP_SECRET=7fe58b36520fb4767584231b04836e13
   export DATABASE_URL=mysql://mintme:sN09YUU3dX@10.81.143.1:3306/$BRANCH_DB
   export MAILER_TRANSPORT=smtp
   export MAILER_HOST=mail.abchosting.org
   export MAILER_USER=mintme@mintme.abchosting.org
   export MAILER_PORT=587
   export MAILER_ENCRYPTION=tls
   export MAILER_AUTH_MODE=login
   export MAILER_PASSWORD=92HGRk5h
   export YOUTUBE_CLIENT=984627114486-t0iu4kij0spd19r6er8omo9jj0h1dpas.apps.googleusercontent.com
   export FACEBOOK_CLIENT=243635433206441
#   export GOOGLE_RECAPTCHA_SITE_KEY=6LduKI0UAAAAAD5d5orcDi1hQWm-TuZ-QF_l2Ks7
   export GOOGLE_RECAPTCHA_SITE_KEY=6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI
   export VIABTC_FORCE_WS_AUTH=0;
   export VIABTC_OFFSET=0;
   export RABBITMQ_URL=amqp://guest:guest@localhost:5672
   export DEPOSIT_RPC=http://localhost:3000
   export WITHDRAW_RPC=http://localhost:8743
   export VIABTC_RPC=http://localhost:8080
   export WEBSOCKET_URL=ws://10.81.143.6:8364
   export COINIFY_SHARED_SECRET=458e14ea-212a-4658-af0f-f580863ac126
   export TWITTER_API_KEY=m6Clz3zmCTK16viW5b3Z4s8gS
   export TWITTER_API_SECRET=b7jTvT4p5Y4C0hizSFydozV2mPujdcdNKD5W9StsR3McjbVihg
   export TWITTER_API_BEARER_TOKEN=AAAAAAAAAAAAAAAAAAAAALgILQEAAAAAUsI5eF194mctK2NjAfz5t4u3a4I%3DjorLrk8sPEOc0pjAs6QeMOdrp3BLj4IIsT0p6SGMlq8WjVEyit
   export MERCURE_PUBLISH_URL=http://localhost:3011/.well-known/mercure
   export MERCURE_JWT_TOKEN=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtZXJjdXJlIjp7InB1Ymxpc2giOltdfX0.NsZebnlGaA468bjcrqOdh80o6EDHlyIwReB2-UqsygI
   export DISCORD_BOT_TOKEN=ODI5NTIzOTUxNDc0MTgwMTQ3.YG5YYQ.fsIAt47EyrkR77L5oXLB3jZNpLs
   export DISCORD_CLIENT_ID=829523951474180147
   export DISCORD_CLIENT_SECRET=lXnpqYf7kFB3ySucI9GHyJe52dVujifH
   export DISCORD_PUBLIC_KEY=3bba5001355af279df95bb235d12df88eccf7d261bd7836c0f99e3a7f5c240f3

   echo "Environment variables set for old test environment"
fi
list_comm=$(sudo --user=mintme -E php bin/console| grep -Pzo '.*Available commands(.*\n)*' | tail --line=+2| awk '{print $1}'|sed '$d'| grep :)
if [[ $(echo $list_comm| grep -w "$1") ]]; then
#   echo APP_ENV set to $APP_ENV
#   echo DATABASE_URL set to $DATABASE_URL
#   echo VIABTC_OFFSET set to $VIABTC_OFFSET
#   echo RABBIT_URL set to $RABBITMQ_URL
#   echo DEPOSIT_RPC set to $DEPOSIT_RPC
#   echo WITHDRAW_RPC set to $WITHDRAW_RPC
#   echo VIABTC_RPC set to $VIABTC_RPC
#   echo WEBSOCKET_URL set to $WEBSOCKET_URL
#	sudo --user=mintme fix_owner_var_cache.sh
	if [[ $(echo $1 | grep -w "doctrine:database:drop") ]]; then
		echo "delete panel database is disabled for symfony-run.sh and only can be done by using clear-panel-db.sh script"
		exit 1
	fi
	sudo --user=mintme -E  php bin/console $exec_command	
else
	echo "invalid Symfony command"
fi
