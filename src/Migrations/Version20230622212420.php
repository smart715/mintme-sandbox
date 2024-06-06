<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230622212420 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add native subunit for each crypto';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE crypto ADD native_subunit INT NOT NULL');
        $this->addSql('UPDATE crypto SET native_subunit = 18 WHERE symbol = "WEB"');
        $this->addSql('UPDATE crypto SET native_subunit = 8 WHERE symbol = "BTC"');
        $this->addSql('UPDATE crypto SET native_subunit = 18 WHERE symbol = "ETH"');
        $this->addSql('UPDATE crypto SET native_subunit = 12 WHERE symbol = "USDC"');
        $this->addSql('UPDATE crypto SET native_subunit = 18 WHERE symbol = "BNB"');
        $this->addSql('UPDATE crypto SET native_subunit = 18 WHERE symbol = "CRO"');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE crypto DROP native_subunit');
    }
}
