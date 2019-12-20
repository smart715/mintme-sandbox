#!/bin/bash
git submodule update --init --recursive && git submodule update --remote .docker/deposit
sed -i "s/100000; ++i/100; ++i/" .docker/viabtc/btc/source/marketprice/mp_message.c