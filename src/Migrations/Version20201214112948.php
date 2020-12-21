<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201214112948 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE token ADD exchange_crypto_id INT DEFAULT NULL, CHANGE withdrawn withdrawn VARCHAR(255) NOT NULL, CHANGE airdrops_amount airdrops_amount VARCHAR(255) NOT NULL, CHANGE number_of_reminder number_of_reminder SMALLINT NOT NULL, CHANGE next_reminder_date next_reminder_date DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE token ADD CONSTRAINT FK_5F37A13BA47F8342 FOREIGN KEY (exchange_crypto_id) REFERENCES crypto (id)');
        $this->addSql('CREATE INDEX IDX_5F37A13BA47F8342 ON token (exchange_crypto_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE token DROP FOREIGN KEY FK_5F37A13BA47F8342');
        $this->addSql('DROP INDEX IDX_5F37A13BA47F8342 ON token');
        $this->addSql('ALTER TABLE token DROP exchange_crypto_id, CHANGE withdrawn withdrawn VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'0\' NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE airdrops_amount airdrops_amount VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'0\' NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE number_of_reminder number_of_reminder SMALLINT DEFAULT 0 NOT NULL, CHANGE next_reminder_date next_reminder_date DATE DEFAULT NULL');
    }
}
