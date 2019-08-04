#!/usr/bin/env bash

until nc -v -z -w30 db 3306
do
    echo "Waiting for db connection..."
    sleep 5
done

/btc/start.sh
