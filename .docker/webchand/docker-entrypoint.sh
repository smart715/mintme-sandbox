#!/usr/bin/env bash
_PASS=${PASS:=/data/password}
_ACCOUNT=${ACCOUNT:=/data/private}
_IS_FAST=${IS_FAST:=1}
_RPC_HOST=${RPC_HOST:="0.0.0.0"}
_RPC_PORT=${RPC_PORT:=39573}
_RPC_CORS=${RPC_CORS:="*"}
_RPC_API=${RPC_API:="admin,debug,webchain,miner,eth,net,web3"}


webchaind  --password ${_PASS} account import ${_ACCOUNT}
webchaind $(if [ ${_IS_FAST} -eq 1 ]; then echo '--fast'; fi) \
    --maxpeers 0 \
    --rpc \
    --rpc-addr "${_RPC_HOST}" \
    --rpc-port "${_RPC_PORT}" \
    --rpc-cors-domain "${_RPC_CORS}" \
    --rpc-api "${_RPC_API}" \
    --unlock 0 \
    --password ${_PASS}
