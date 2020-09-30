<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200825163628 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
        'CREATE TABLE comment (
                id INT AUTO_INCREMENT NOT NULL,
                content LONGTEXT DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                post_id INT NOT NULL,
                user_id INT NOT NULL,
                like_count INT NOT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_Comments_Posts FOREIGN KEY (post_id) REFERENCES post(id)');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_Comments_Users FOREIGN KEY (user_id) REFERENCES user(id)');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_Comments_Posts');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_Comments_Users');
        $this->addSql('DROP TABLE comment');
    }
}
