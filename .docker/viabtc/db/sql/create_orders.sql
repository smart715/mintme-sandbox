create database trade_market;
USE trade_market;

CREATE TABLE `markets` (
    `id`            BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    `market`        VARCHAR(30) NOT NULL,
    `in_memory`     TINYINT(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `orders_example` (
    `id`            BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    `t`             TINYINT UNSIGNED NOT NULL,
    `side`          TINYINT UNSIGNED NOT NULL,
    `create_time`   DOUBLE NOT NULL,
    `update_time`   DOUBLE NOT NULL,
    `user_id`       INT UNSIGNED NOT NULL,
    `market`        VARCHAR(30) NOT NULL,
    `price`         DECIMAL(30,8) NOT NULL,
    `amount`        DECIMAL(30,8) NOT NULL,
    `taker_fee`     DECIMAL(30,4) NOT NULL,
    `maker_fee`     DECIMAL(30,4) NOT NULL,
    `left`          DECIMAL(30,8) NOT NULL,
    `freeze`        DECIMAL(30,8) NOT NULL,
    `deal_stock`    DECIMAL(30,8) NOT NULL,
    `deal_money`    DECIMAL(30,16) NOT NULL,
    `deal_fee`      DECIMAL(30,12) NOT NULL,
    `unused_fee`    DECIMAL(30,12) NOT NULL,
    `referral_id`   INT UNSIGNED NOT NULL,
    `referral_fee`  DECIMAL(30,12) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
