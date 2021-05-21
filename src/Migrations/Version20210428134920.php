<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210428134920 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add Binance to the crypto table to bind bnb tokens with crypto entity';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
            INSERT INTO `crypto` (`name`, `symbol`, `subunit`, `fee`, `tradable`, `exchangeble`, `show_subunit`, `image_path`, `is_token`) 
            VALUES (\'Binance Coin\', \'BNB\', 18, 3000000000000000, 0, 0, 8, \'/media/default_bnb.png\', 0);');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DELETE FROM crypto WHERE name = \'Binance Coin\';');
    }
}
