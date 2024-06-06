<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230413041543 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '#9383 Crypto fixes, USDC migration';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE crypto CHANGE symbol symbol VARCHAR(20) NOT NULL, CHANGE fee fee BIGINT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_682828855E237E06 ON crypto (name)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_68282885ECC836F9 ON crypto (symbol)');

        $this->addSql('ALTER TABLE wrapped_crypto_token ADD fee_currency VARCHAR(255) NOT NULL');

        // Filling current wrapped crypto fee currencies
        $this->addSql('UPDATE wrapped_crypto_token AS wct SET fee_currency = (SELECT symbol FROM crypto WHERE crypto.id = wct.crypto_deploy_id)');

        // Migrating USDC to use WrappedCryptoToken
        $this->addSql('
            INSERT INTO wrapped_crypto_token (crypto_id, crypto_deploy_id, address, fee, fee_currency) 
            VALUES (
                (SELECT id FROM crypto WHERE symbol = "USDC"), 
                (SELECT id FROM crypto WHERE symbol  = "ETH"), 
                "0xa0b86991c6218b36c1d19d4a2e9eb0ce3606eb48",
                (SELECT fee FROM crypto WHERE symbol = "USDC"),
                "USDC"
            )
        ');
        $this->addSql('UPDATE crypto SET fee = NULL WHERE symbol = "USDC"');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX UNIQ_682828855E237E06 ON crypto');
        $this->addSql('DROP INDEX UNIQ_68282885ECC836F9 ON crypto');
        $this->addSql('ALTER TABLE crypto CHANGE symbol symbol VARCHAR(5) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE fee fee BIGINT NOT NULL');

        $this->addSql('UPDATE crypto SET fee = (SELECT fee FROM wrapped_crypto_token WHERE fee_currency = "USDC") WHERE symbol = "USDC";');
        $this->addSql('DELETE FROM wrapped_crypto_token WHERE id = (SELECT id FROM crypto WHERE symbol = "USDC")');

        $this->addSql('ALTER TABLE wrapped_crypto_token DROP fee_currency');
    }
}
