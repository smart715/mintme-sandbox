<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240331140007 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add native_coin_id to crypto table';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE crypto ADD native_coin_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE crypto ADD CONSTRAINT FK_68282885BDCF0004 FOREIGN KEY (native_coin_id) REFERENCES crypto (id)');
        $this->addSql('UPDATE crypto SET native_coin_id = (select id from crypto where symbol = "ETH") WHERE symbol = "ARB"');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE crypto DROP FOREIGN KEY FK_68282885BDCF0004');
        $this->addSql('ALTER TABLE crypto DROP native_coin_id');
    }
}
