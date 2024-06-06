#!/bin/bash

killall -s SIGQUIT marketprice
sleep 1
cd /btc/marketprice && ./marketprice -log_dir=/var/log/trade config.json&
