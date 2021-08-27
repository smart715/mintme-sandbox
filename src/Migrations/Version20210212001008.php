<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210212001008 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql("ALTER TABLE market_status ADD last_deal_id INT DEFAULT 0 NOT NULL");
    }

    public function down(Schema $schema) : void
    {
        $this->addSql("ALTER TABLE market_status DROP last_deal_id");
    }
}
