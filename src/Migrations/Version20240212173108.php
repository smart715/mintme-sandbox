<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240212173108 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add Arbitrum as token';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
            INSERT INTO `crypto` (`name`, `symbol`, `subunit`, `native_subunit`, `fee`, `tradable`, `exchangeble`, `show_subunit`, `image_path`, `is_token`) 
            VALUES (\'Arbitrum\', \'ARB\', 18, 18, 1000000000000000000, 1, 1, 8, \'/media/default_arb.svg\', 1);');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DELETE FROM crypto WHERE name = \'Arbitrum\';');
    }
}
