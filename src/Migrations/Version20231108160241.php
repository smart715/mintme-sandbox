<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231108160241 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Create user_change_email_request table and add foreign keys for email change functionality.';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE user_change_email_request (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT DEFAULT NULL,
            old_email VARCHAR(320) NOT NULL,
            new_email VARCHAR(320) NOT NULL,
            created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            confirmed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_2491F396A76ED395 (user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_change_email_request ADD CONSTRAINT FK_2491F396A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE validation_code ADD user_change_email_request_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE validation_code ADD CONSTRAINT FK_DCC410DF274610FA FOREIGN KEY (user_change_email_request_id) REFERENCES user_change_email_request (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_DCC410DF274610FA ON validation_code (user_change_email_request_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE validation_code DROP FOREIGN KEY FK_DCC410DF274610FA');
        $this->addSql('DROP TABLE user_change_email_request');
        $this->addSql('DROP INDEX IDX_DCC410DF274610FA ON validation_code');
        $this->addSql('ALTER TABLE validation_code DROP user_change_email_request_id');
    }
}
