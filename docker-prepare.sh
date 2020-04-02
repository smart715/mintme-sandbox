#!/bin/bash
git submodule update --init --recursive && git submodule update --remote .docker/deposit && git submodule update --init --recursive --remote .docker/viabtc/btc/source
