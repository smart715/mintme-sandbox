<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201127182845 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE token DROP INDEX UNIQ_5F37A13BCCFA12B8, ADD INDEX IDX_5F37A13BCCFA12B8 (profile_id)');
        $this->addSql('ALTER TABLE token ADD crypto_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE token ADD CONSTRAINT FK_5F37A13BE9571A63 FOREIGN KEY (crypto_id) REFERENCES crypto (id)');
        $this->addSql('CREATE INDEX IDX_5F37A13BE9571A63 ON token (crypto_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE token DROP INDEX IDX_5F37A13BCCFA12B8, ADD UNIQUE INDEX UNIQ_5F37A13BCCFA12B8 (profile_id)');
        $this->addSql('ALTER TABLE token DROP FOREIGN KEY FK_5F37A13BE9571A63');
        $this->addSql('DROP INDEX IDX_5F37A13BE9571A63 ON token');
        $this->addSql('ALTER TABLE token DROP crypto_id');
    }
}
