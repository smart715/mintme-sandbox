#!/usr/bin/env bash
_PASS=${PASS:=/data/password}
_ACCOUNT=${ACCOUNT:=/data/private}
_IS_FAST=${IS_FAST:=1}
_RPC_HOST=${RPC_HOST:="0.0.0.0"}
_RPC_PORT=${RPC_PORT:=39573}
_RPC_CORS=${RPC_CORS:="*"}
_RPC_API=${RPC_API:="admin,debug,webchain,miner,eth,net,web3,txpool"}


#webchaind  --password ${_PASS} account import ${_ACCOUNT}
webchaind $(if [ ${_IS_FAST} -eq 1 ]; then echo '--fast'; fi) \
    --bootnodes enode://68d8691c25e6a6c75f2329de8a50341acad79788318260fa91cc623014d3e5c1eb004148e06f0ac1d24da1b92661dcde244f5e1a1d9714177b638dbbc40a6a6d@95.211.188.144:42447 \
    --chain=morden \
    --mine \
    --miner-threads 1 \
    --rpc \
    --rpc-addr "${_RPC_HOST}" \
    --rpc-port "${_RPC_PORT}" \
    --rpc-cors-domain "${_RPC_CORS}" \
    --rpc-api "${_RPC_API}" \
    --unlock 0x9139413fe7247da35ae0797f9270271ba758d248 \
    --password ${_PASS}
