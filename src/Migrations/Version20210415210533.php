<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210415210533 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE activity ADD COLUMN currency VARCHAR(255) DEFAULT NULL');
        $this->addSql("UPDATE activity SET currency = 'USD' WHERE type IN (0, 3, 7, 8, 9)");
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE activity DROP COLUMN currency');
    }
}
