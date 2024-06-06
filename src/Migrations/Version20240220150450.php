<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240220150450 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Allow null address for wrapped cryptok token and add Arbitrum as network for ETH';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE wrapped_crypto_token CHANGE address address VARCHAR(255) DEFAULT NULL');
        $this->addSql('
            INSERT INTO wrapped_crypto_token (crypto_id, crypto_deploy_id, address, fee, fee_currency) 
            VALUES (
                (SELECT id FROM crypto WHERE symbol = "ETH"), 
                (SELECT id FROM crypto WHERE symbol  = "ARB"), 
                null,
                (SELECT fee FROM crypto WHERE symbol = "ETH"),
                "ETH"
            )
        ');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DELETE FROM wrapped_crypto_token WHERE crypto_id = (SELECT id FROM crypto WHERE symbol = "ETH") AND crypto_deploy_id = (SELECT id FROM crypto WHERE symbol = "ARB")');
        $this->addSql('ALTER TABLE wrapped_crypto_token CHANGE address address VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
