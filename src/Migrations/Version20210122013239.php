<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210122013239 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Create activity table';
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE activity (
            id INT AUTO_INCREMENT NOT NULL,
            type INT NOT NULL,
            token_id INT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            amount VARCHAR(255) DEFAULT NULL,
            user1_id INT DEFAULT NULL,
            user2_id INT DEFAULT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        $this->addSql('ALTER TABLE activity ADD CONSTRAINT FK_Activities_Tokens FOREIGN KEY (token_id) REFERENCES token(id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE activity ADD CONSTRAINT FK_Activities_Users_1 FOREIGN KEY (user1_id) REFERENCES user(id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE activity ADD CONSTRAINT FK_Activities_Users_2 FOREIGN KEY (user2_id) REFERENCES user(id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE activity DROP FOREIGN KEY FK_Activities_Tokens');
        $this->addSql('ALTER TABLE activity DROP FOREIGN KEY FK_Activities_Users_1');
        $this->addSql('ALTER TABLE activity DROP FOREIGN KEY FK_Activities_Users_2');

        $this->addSql('DROP TABLE activity');
    }
}
