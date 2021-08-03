<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210712162846 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE discord_role DROP CONSTRAINT UQ_DiscordRole_Token_Name');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE discord_role ADD CONSTRAINT UQ_DiscordRole_Token_Name UNIQUE (token_id, name)');
    }
}
