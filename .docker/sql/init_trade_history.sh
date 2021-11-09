#!/bin/bash

MYSQL_HOST="localhost"
MYSQL_USER="root"
MYSQL_PASS="root"
MYSQL_DB="trade_history"

for i in `seq 0 99`
do
    echo "create table balance_history_$i"
    mysql -h$MYSQL_HOST -u$MYSQL_USER -p$MYSQL_PASS $MYSQL_DB -e "CREATE TABLE balance_history_$i LIKE balance_history_example;"
done

for i in `seq 0 99`
do
    echo "create table order_history_$i"
    mysql -h$MYSQL_HOST -u$MYSQL_USER -p$MYSQL_PASS $MYSQL_DB -e "CREATE TABLE order_history_$i LIKE order_history_example;"
done

for i in `seq 0 99`
do
    echo "create table order_detail_$i"
    mysql -h$MYSQL_HOST -u$MYSQL_USER -p$MYSQL_PASS $MYSQL_DB -e "CREATE TABLE order_detail_$i LIKE order_detail_example;"
done

for i in `seq 0 99`
do
    echo "create table deal_history_$i"
    mysql -h$MYSQL_HOST -u$MYSQL_USER -p$MYSQL_PASS $MYSQL_DB -e "CREATE TABLE deal_history_$i LIKE deal_history_example;"
done

for i in `seq 0 99`
do
    echo "create table user_deal_history_$i"
    mysql -h$MYSQL_HOST -u$MYSQL_USER -p$MYSQL_PASS $MYSQL_DB -e "CREATE TABLE user_deal_history_$i LIKE user_deal_history_example;"
done

for i in `seq 0 99`
do
    echo "create table orders_$i"
    mysql -h$MYSQL_HOST -u$MYSQL_USER -p$MYSQL_PASS "trade_market" -e "CREATE TABLE orders_$i LIKE orders_example;"
done

for i in `seq 0 99`
do
    echo "create dicts_$i"
    mysql -h$MYSQL_HOST -u$MYSQL_USER -p$MYSQL_PASS "marketprice" -e "CREATE TABLE dicts_$i LIKE dicts_example;"
done

for i in `seq 0 99`
do
    echo "create lists_$i"
    mysql -h$MYSQL_HOST -u$MYSQL_USER -p$MYSQL_PASS "marketprice" -e "CREATE TABLE lists_$i LIKE lists_example;"
done
