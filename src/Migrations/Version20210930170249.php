<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210930170249 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'ON DELETE CASCADE for quote_crypto id and crypto_id(market_status) and for crypto_id (token_crypto)';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE token_crypto DROP FOREIGN KEY FK_69508787E9571A63');
        $this->addSql('ALTER TABLE token_crypto ADD CONSTRAINT FK_69508787E9571A63 FOREIGN KEY (crypto_id) REFERENCES crypto (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE market_status DROP FOREIGN KEY FK_5BA4CE106EB58319');
        $this->addSql('ALTER TABLE market_status DROP FOREIGN KEY FK_5BA4CE10E9571A63');
        $this->addSql('ALTER TABLE market_status ADD CONSTRAINT FK_5BA4CE106EB58319 FOREIGN KEY (quote_crypto_id) REFERENCES crypto (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE market_status ADD CONSTRAINT FK_5BA4CE10E9571A63 FOREIGN KEY (crypto_id) REFERENCES crypto (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE market_status DROP FOREIGN KEY FK_5BA4CE10E9571A63');
        $this->addSql('ALTER TABLE market_status DROP FOREIGN KEY FK_5BA4CE106EB58319');
        $this->addSql('ALTER TABLE market_status ADD CONSTRAINT FK_5BA4CE10E9571A63 FOREIGN KEY (crypto_id) REFERENCES crypto (id)');
        $this->addSql('ALTER TABLE market_status ADD CONSTRAINT FK_5BA4CE106EB58319 FOREIGN KEY (quote_crypto_id) REFERENCES crypto (id)');
        $this->addSql('ALTER TABLE token_crypto DROP FOREIGN KEY FK_69508787E9571A63');
        $this->addSql('ALTER TABLE token_crypto ADD CONSTRAINT FK_69508787E9571A63 FOREIGN KEY (crypto_id) REFERENCES crypto (id)');
    }
}
