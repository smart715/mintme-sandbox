<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231013053953 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Added token column to scheduled notifications';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE scheduled_notifications ADD token_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE scheduled_notifications ADD CONSTRAINT FK_E7DE778141DEE7B9 FOREIGN KEY (token_id) REFERENCES token (id)');
        $this->addSql('CREATE INDEX IDX_E7DE778141DEE7B9 ON scheduled_notifications (token_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE scheduled_notifications DROP FOREIGN KEY FK_E7DE778141DEE7B9');
        $this->addSql('DROP INDEX IDX_E7DE778141DEE7B9 ON scheduled_notifications');
        $this->addSql('ALTER TABLE scheduled_notifications DROP token_id');
    }
}
