<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201201030607 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql(
            'CREATE TABLE post_users_share_reward (
                id INT AUTO_INCREMENT NOT NULL,
                post_id INT NOT NULL,
                user_id INT NOT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        $this->addSql('ALTER TABLE `post_users_share_reward` ADD CONSTRAINT FK_SR_Posts FOREIGN KEY (post_id) REFERENCES post(id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `post_users_share_reward` ADD CONSTRAINT FK_SR_Users FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE');

    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE `post_users_share_reward` DROP FOREIGN KEY FK_SR_Posts');
        $this->addSql('ALTER TABLE `post_users_share_reward` DROP FOREIGN KEY FK_SR_Users');
        $this->addSql('DROP TABLE `post_users_share_reward`');
    }
}
