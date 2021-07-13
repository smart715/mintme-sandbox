<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210410025344 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'create discord_role table';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('CREATE TABLE discord_role (
            id INT AUTO_INCREMENT NOT NULL,
            discord_id BIGINT NOT NULL,
            name VARCHAR(255) NOT NULL,
            token_id INT NOT NULL,
            required_balance VARCHAR(255) NOT NULL,
            color INT NOT NULL,
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        $this->addSql('ALTER TABLE discord_role ADD CONSTRAINT FK_DiscordRole_Token FOREIGN KEY (token_id) REFERENCES token(id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE discord_role ADD CONSTRAINT UQ_DiscordRole_Token_Name UNIQUE (token_id, name)');
        $this->addSql('ALTER TABLE discord_role ADD CONSTRAINT UQ_DiscordRole_Token_RequiredBalance UNIQUE (token_id, required_balance)');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE discord_role DROP FOREIGN KEY FK_DiscordRole_Token');
        $this->addSql('ALTER TABLE discord_role DROP CONSTRAINT UQ_DiscordRole_Token_Name');
        $this->addSql('ALTER TABLE discord_role DROP CONSTRAINT UQ_DiscordRole_Token_RequiredBalance');

        $this->addSql('DROP TABLE discord_role');
    }
}
