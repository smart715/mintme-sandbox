<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231215175034 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update fee for USDC and BTC for test and dev env (prod was updated using app:crypto:change-subunit)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('UPDATE crypto SET fee = 500000000000000 WHERE symbol = "BTC"');
        $this->addSql('UPDATE wrapped_crypto_token SET fee = 14000000000000000000 WHERE crypto_id = 4 AND crypto_deploy_id = 3 AND fee_currency = "USDC"');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('UPDATE crypto SET fee = 50000 WHERE symbol = "BTC"');
        $this->addSql('UPDATE wrapped_crypto_token SET fee = 14000000 WHERE crypto_id = 4 AND crypto_deploy_id = 3 AND fee_currency = "USDC"');
    }
}
