#!/bin/bash

killall -s SIGQUIT marketprice
sleep 1
cd /btc/marketprice && ./marketprice config.json
