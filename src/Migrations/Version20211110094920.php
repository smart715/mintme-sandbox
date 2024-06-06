<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211110094920 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Added arguments to change_percentage function, because it is required for to_number function';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("DROP FUNCTION IF EXISTS change_percentage");

        $sql1 = <<< SQL
        CREATE FUNCTION change_percentage (last_price VARCHAR(255), open_price VARCHAR(255), subunit INT, show_subunit INT) RETURNS DECIMAL(16,2) DETERMINISTIC
        BEGIN
            DECLARE open_price1 BIGINT SIGNED;
            DECLARE last_price1 BIGINT SIGNED;
            SELECT to_number(open_price, subunit, show_subunit) INTO open_price1;
            SELECT to_number(last_price, subunit, show_subunit) INTO last_price1;
        
            RETURN CASE WHEN open_price1 = 0
                THEN 0
                ELSE CAST(((last_price1 - open_price1) / open_price1) AS DECIMAL(16, 2))
                END;
        END;
        SQL;
        
        $this->addSql($sql1);
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("DROP FUNCTION IF EXISTS change_percentage");

        $sql1 = <<< SQL
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

        $this->addSql($sql1);
    }
}
