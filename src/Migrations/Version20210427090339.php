<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210427090339 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE voting ADD crypto_id INT NOT NULL, ADD discr VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE voting ADD CONSTRAINT FK_FC28DA55E9571A63 FOREIGN KEY (crypto_id) REFERENCES crypto (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_FC28DA55E9571A63 ON voting (crypto_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE voting DROP FOREIGN KEY FK_FC28DA55E9571A63');
        $this->addSql('DROP INDEX IDX_FC28DA55E9571A63 ON voting');
        $this->addSql('ALTER TABLE voting DROP crypto_id, DROP discr');
    }
}
