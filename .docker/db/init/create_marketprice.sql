create database marketprice;
USE marketprice;

CREATE TABLE `markets` (
    `id`            BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    `market`        VARCHAR(30) NOT NULL,
    `in_memory`     TINYINT(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `dicts_example` (
    `market`        VARCHAR(30) NOT NULL,
    `duration`      TINYINT(1) NOT NULL,
    `time`          BIGINT(20) NOT NULL,
    `open`          DECIMAL(30,8) NOT NULL,
    `close`         DECIMAL(30,8) NOT NULL,
    `high`          DECIMAL(30,8) NOT NULL,
    `low`           DECIMAL(30,8) NOT NULL,
    `volume`        DECIMAL(30,8) NOT NULL,
    `deal`          DECIMAL(30,8) NOT NULL,
    PRIMARY KEY (`market`, `duration`, `time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `lists_example` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `market`        VARCHAR(30) NOT NULL,
    `value`         TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
