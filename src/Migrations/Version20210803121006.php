<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210803121006 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Set exchangeble for BTC, ETH, USDC and BNB coins';
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('UPDATE crypto SET exchangeble = 1 WHERE symbol = \'BTC\';');
        $this->addSql('UPDATE crypto SET exchangeble = 1 WHERE symbol = \'ETH\';');
        $this->addSql('UPDATE crypto SET exchangeble = 1 WHERE symbol = \'USDC\';');
        $this->addSql('UPDATE crypto SET exchangeble = 1 WHERE symbol = \'BNB\';');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('UPDATE crypto SET exchangeble = 0 WHERE symbol = \'BTC\';');
        $this->addSql('UPDATE crypto SET exchangeble = 0 WHERE symbol = \'ETH\';');
        $this->addSql('UPDATE crypto SET exchangeble = 0 WHERE symbol = \'USDC\';');
        $this->addSql('UPDATE crypto SET exchangeble = 0 WHERE symbol = \'BNB\';');
    }
}
