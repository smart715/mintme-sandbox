#!/bin/bash

killall -s SIGQUIT matchengine
sleep 1
cd /btc/matchengine && ./matchengine -log_dir=/var/log/trade config.json&
