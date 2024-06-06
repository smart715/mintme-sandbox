<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240219173211 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Arbitrum wrapped crypto token';
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
            INSERT INTO wrapped_crypto_token (crypto_id, crypto_deploy_id, address, fee, fee_currency) 
            VALUES (
                (SELECT id FROM crypto WHERE symbol = "ARB"), 
                (SELECT id FROM crypto WHERE symbol  = "ARB"), 
                "0x912CE59144191C1204E64559FE8253a0e49E6548",
                (SELECT fee FROM crypto WHERE symbol = "ARB"),
                "ARB"
            )
        ');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DELETE FROM wrapped_crypto_token WHERE crypto_id = (SELECT id FROM crypto WHERE symbol = "ARB") AND crypto_deploy_id = (SELECT id FROM crypto WHERE symbol = "ARB")');
    }
}
