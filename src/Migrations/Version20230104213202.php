<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230104213202 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user_notifications_config ADD token_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user_notifications_config ADD token_post_enabled TINYINT(1) DEFAULT \'1\' NOT NULL');
        $this->addSql('ALTER TABLE user_notifications_config ADD CONSTRAINT FK_285FE62E41DEE7B9 FOREIGN KEY (token_id) REFERENCES token (id)');
        $this->addSql('CREATE INDEX IDX_285FE62E41DEE7B9 ON user_notifications_config (token_id)');

    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user_notifications_config DROP FOREIGN KEY FK_285FE62E41DEE7B9');
        $this->addSql('DROP INDEX IDX_285FE62E41DEE7B9 ON user_notifications_config');
        $this->addSql('ALTER TABLE user_notifications_config DROP token_id');
        $this->addSql('ALTER TABLE user_notifications_config DROP token_post_enabled');
    }
}