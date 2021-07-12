<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210428195123 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('CREATE TABLE discord_role_user (
            user_id INT NOT NULL,
            token_id INT NOT NULL,
            role_id INT NOT NULL,
            PRIMARY KEY (user_id, token_id),
            CONSTRAINT FK_Discord_Role_User_User FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
            CONSTRAINT FK_Discord_Role_User_Token FOREIGN KEY (token_id) REFERENCES token(id) ON DELETE CASCADE,
            CONSTRAINT FK_Discord_Role_User_Role FOREIGN KEY (role_id) REFERENCES discord_role(id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE discord_role_user DROP FOREIGN KEY FK_Discord_Role_User_User');
        $this->addSql('ALTER TABLE discord_role_user DROP FOREIGN KEY FK_Discord_Role_User_Token');
        $this->addSql('ALTER TABLE discord_role_user DROP FOREIGN KEY FK_Discord_Role_User_Role');

        $this->addSql('DROP TABLE discord_role_user');
    }
}
