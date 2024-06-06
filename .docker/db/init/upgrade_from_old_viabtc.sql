-- should be called on db_market
USE trade_market;

CREATE TABLE `balance_example` (
                                   `user_id`       INT UNSIGNED NOT NULL,
                                   `asset`         VARCHAR(30) NOT NULL,
                                   `type`          INT UNSIGNED NOT NULL,
                                   `balance`       DECIMAL(65,16) NOT NULL,
                                   PRIMARY KEY (`user_id`, `asset`, `type`),
                                   INDEX `idx_user_asset` (`user_id`, `asset`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP PROCEDURE IF EXISTS `create_tables`;
DELIMITER //
CREATE PROCEDURE create_tables(tableName VARCHAR(100), n INT)
BEGIN
    WHILE n > 0
        DO
            SET n = n - 1;
            SET @sql = CONCAT('CREATE TABLE ', tableName, '_', n, ' LIKE ', tableName, '_example');
            PREPARE cmd FROM @sql;
            EXECUTE cmd;
            DEALLOCATE PREPARE cmd;
        END WHILE;
END; //
DELIMITER ;

CALL create_tables('balance', 100);


CREATE INDEX `orders_market` ON `orders_example` (`market`);

DROP PROCEDURE IF EXISTS `create_tables`;
DELIMITER //
CREATE PROCEDURE create_tables(tableName VARCHAR(100), n INT)
BEGIN
    WHILE n > 0
        DO
            SET n = n - 1;
            SET @sql = CONCAT('CREATE INDEX `orders_market` ON `', tableName, '_', n, '` (`market`)');
            PREPARE cmd FROM @sql;
            EXECUTE cmd;
            DEALLOCATE PREPARE cmd;
        END WHILE;
END; //
DELIMITER ;

CALL create_tables('orders', 100);
