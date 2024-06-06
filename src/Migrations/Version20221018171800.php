<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221018171800 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
        <<< SQL
            ALTER TABLE market_status
            CHANGE open_price open_price NUMERIC(65) NOT NULL, 
            CHANGE last_price last_price NUMERIC(65) NOT NULL, 
            CHANGE day_volume day_volume NUMERIC(65) NOT NULL, 
            CHANGE month_volume month_volume NUMERIC(65) NOT NULL, 
            CHANGE buy_depth buy_depth NUMERIC(65) NOT NULL, 
            CHANGE sold_on_market sold_on_market NUMERIC(65) DEFAULT '0' NOT NULL,
            CHANGE IF EXISTS volume_total volume_total NUMERIC(65) NOT NULL
        SQL
        );
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
        <<< SQL
            ALTER TABLE market_status
            CHANGE open_price open_price VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, 
            CHANGE last_price last_price VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, 
            CHANGE day_volume day_volume VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, 
            CHANGE month_volume month_volume VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, 
            CHANGE buy_depth buy_depth VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT '0' NOT NULL COLLATE `utf8mb4_unicode_ci`,
            CHANGE sold_on_market sold_on_market VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT '0' NOT NULL COLLATE `utf8mb4_unicode_ci`,
            CHANGE volume_total volume_total VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT '0' COLLATE `utf8mb4_unicode_ci`
        SQL
        );
    }
}
