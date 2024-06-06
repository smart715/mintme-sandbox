<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210907171447 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Unique constraints for token_crypto';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE token_crypto CHANGE crypto_id crypto_id INT NOT NULL, CHANGE token_id token_id INT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX token_crypto_index ON token_crypto (crypto_id, token_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX token_crypto_index ON token_crypto');
        $this->addSql('ALTER TABLE token_crypto CHANGE crypto_id crypto_id INT DEFAULT NULL, CHANGE token_id token_id INT DEFAULT NULL');
    }
}
