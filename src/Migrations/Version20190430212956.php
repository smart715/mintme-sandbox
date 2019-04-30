<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190430212956 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE market_status ADD token_id INT DEFAULT NULL, ADD currency VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE market_status ADD CONSTRAINT FK_5BA4CE1041DEE7B9 FOREIGN KEY (token_id) REFERENCES token (id)');
        $this->addSql('CREATE INDEX IDX_5BA4CE1041DEE7B9 ON market_status (token_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE market_status DROP FOREIGN KEY FK_5BA4CE1041DEE7B9');
        $this->addSql('DROP INDEX IDX_5BA4CE1041DEE7B9 ON market_status');
        $this->addSql('ALTER TABLE market_status DROP token_id, DROP currency');
    }
}
