<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211108143818 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Added subunit and show_subunit args to marketcap because to_number needs 3 arguments';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("DROP FUNCTION IF EXISTS marketcap");
        $sql1 = <<< SQL
        CREATE FUNCTION marketcap (last_price VARCHAR(255), month_volume VARCHAR(255), min_volume BIGINT SIGNED, subunit INT, show_subunit INT) RETURNS BIGINT SIGNED DETERMINISTIC
        BEGIN
            DECLARE last_price1 BIGINT SIGNED;
            DECLARE month_volume1 BIGINT SIGNED;
            SELECT to_number(last_price, subunit, show_subunit) INTO last_price1;
            SELECT to_number(month_volume, subunit, show_subunit) INTO month_volume1;
        
            RETURN CASE WHEN month_volume1 >= min_volume
                THEN last_price1
                ELSE 0
                END;
        END;
        SQL;
        $this->addSql($sql1);
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("DROP FUNCTION IF EXISTS marketcap");
        $sql1 = <<< SQL
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
    }
}
