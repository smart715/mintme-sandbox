<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220426131143 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add CRO to the Crypto table';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('INSERT INTO `crypto` (`name`, `symbol`, `subunit`, `fee`, `tradable`, `exchangeble`, `show_subunit`, `image_path`, `is_token`)
            VALUES (\'CRONOS\', \'CRO\', 8, 30000, 1, 1, 8, \'/media/default_cro.svg\', 0);');
        $this->addSql('UPDATE crypto SET image_path="/media/default_bnb.svg" WHERE symbol="BNB"');

    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DELETE FROM crypto WHERE name = \'CRONOS\';');
        $this->addSql('UPDATE crypto SET image_path="/media/default_bnb.png" WHERE symbol="BNB"');
    }
}
