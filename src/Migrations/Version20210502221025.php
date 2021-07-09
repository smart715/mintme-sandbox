<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210502221025 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql("DROP FUNCTION IF EXISTS to_number");
        $sql1 = <<< SQL
CREATE FUNCTION to_number (number VARCHAR(255), subunit INT, show_subunit INT) RETURNS BIGINT SIGNED DETERMINISTIC
BEGIN
    DECLARE len INT;
    SELECT CHAR_LENGTH(number) - (subunit - show_subunit) INTO len;
    RETURN CASE WHEN len <= 0
        THEN 0
        ELSE CAST(LEFT(number, len) AS SIGNED)
        END;
END;
SQL;
        $this->addSql($sql1);

    }

    public function down(Schema $schema) : void
    {
        $this->addSql("DROP FUNCTION IF EXISTS to_number");
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
        $this->addSql($sql1);
    }
}
