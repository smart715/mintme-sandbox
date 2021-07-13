<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210425002012 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE user ADD COLUMN discord_id BIGINT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT UQ_DiscordId UNIQUE (discord_id)');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE user DROP CONSTRAINT UQ_DiscordId');
        $this->addSql('ALTER TABLE user DROP COLUMN discord_id');
    }
}
