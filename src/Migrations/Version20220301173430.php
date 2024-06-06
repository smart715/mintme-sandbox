<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220301173430 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Added post user likes table';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            'CREATE TABLE post_users_likes (
                id INT AUTO_INCREMENT NOT NULL,
                post_id INT NOT NULL,
                user_id INT NOT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        $this->addSql('ALTER TABLE `post_users_likes` ADD CONSTRAINT FK_PS_Posts FOREIGN KEY (post_id) REFERENCES post(id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `post_users_likes` ADD CONSTRAINT FK_PS_Users FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE `post` ADD `likes` INT(11) DEFAULT 0');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE `post_users_likes` DROP FOREIGN KEY FK_PS_Posts');
        $this->addSql('ALTER TABLE `post_users_likes` DROP FOREIGN KEY FK_PS_Users');
        $this->addSql('DROP TABLE `post_users_likes`');

        $this->addSql('ALTER TABLE `post` DROP likes');
    }
}
