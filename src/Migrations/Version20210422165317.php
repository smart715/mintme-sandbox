<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210422165317 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'create discord_config table';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('CREATE TABLE discord_config (
            token_id INT NOT NULL,
            guild_id BIGINT DEFAULT NULL,
            special_roles_enabled TINYINT(1) DEFAULT 0,
            enabled TINYINT(1) DEFAULT 0,
            PRIMARY KEY (token_id),
            CONSTRAINT FK_DiscordConfig_Token FOREIGN KEY (token_id) REFERENCES token(id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE discord_config DROP FOREIGN KEY FK_DiscordConfig_Token');

        $this->addSql('DROP TABLE discord_config');
    }
}
