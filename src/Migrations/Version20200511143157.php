<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200511143157 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $sql1 = <<< SQL
CREATE FUNCTION to_number (val VARCHAR(255)) RETURNS BIGINT SIGNED DETERMINISTIC
BEGIN
    DECLARE len INT;
    SELECT CHAR_LENGTH(val) - 14 INTO len;
    RETURN CASE WHEN len <= 0
        THEN 0
        ELSE CAST(LEFT(val, len) AS SIGNED)
        END;
END;
SQL;

        $sql2 = <<< SQL
CREATE FUNCTION change_percentage (last_price VARCHAR(255), open_price VARCHAR(255)) RETURNS DECIMAL(16,2) DETERMINISTIC
BEGIN
    DECLARE open_price1 BIGINT SIGNED;
    DECLARE last_price1 BIGINT SIGNED;
    SELECT to_number(open_price) INTO open_price1;
    SELECT to_number(last_price) INTO last_price1;

    RETURN CASE WHEN open_price1 = 0
        THEN 0
        ELSE CAST(((last_price1 - open_price1) / open_price1) AS DECIMAL(16, 2))
        END;
END;
SQL;

        $sql3 = <<< SQL
CREATE FUNCTION marketcap (last_price VARCHAR(255), month_volume VARCHAR(255), min_volume BIGINT SIGNED) RETURNS BIGINT SIGNED DETERMINISTIC
BEGIN
    DECLARE last_price1 BIGINT SIGNED;
    DECLARE month_volume1 BIGINT SIGNED;
    SELECT to_number(last_price) INTO last_price1;
    SELECT to_number(month_volume) INTO month_volume1;

    RETURN CASE WHEN month_volume1 >= min_volume
        THEN last_price1
        ELSE 0
        END;
END;
SQL;
        $this->addSql($sql1);
        $this->addSql($sql2);
        $this->addSql($sql3);
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql("DROP FUNCTION IF EXISTS change_percentage");
        $this->addSql("DROP FUNCTION IF EXISTS to_number");
        $this->addSql("DROP FUNCTION IF EXISTS marketcap");
    }
}
