<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210607131512 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Set Binance Coin to is tradable and to is token';
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('UPDATE crypto SET tradable = 1, is_token = 1 WHERE name = \'Binance Coin\';');
 }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('UPDATE crypto SET tradable = 0, is_token = 0 WHERE name = \'Binance Coin\';');
    }
}
