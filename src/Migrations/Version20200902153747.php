<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200902153747 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql(
            'CREATE TABLE `like` (
                id INT AUTO_INCREMENT NOT NULL,
                comment_id INT NOT NULL,
                user_id INT NOT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        $this->addSql('ALTER TABLE `like` ADD CONSTRAINT FK_Likes_Comments FOREIGN KEY (comment_id) REFERENCES comment(id)');
        $this->addSql('ALTER TABLE `like` ADD CONSTRAINT FK_Likes_Users FOREIGN KEY (user_id) REFERENCES user(id)');

    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE `like` DROP FOREIGN KEY FK_Likes_Comments');
        $this->addSql('ALTER TABLE `like` DROP FOREIGN KEY FK_Likes_Users');
        $this->addSql('DROP TABLE `like`');
    }
}
