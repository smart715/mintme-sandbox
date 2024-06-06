<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240124194404 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add Avalanche crypto';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
            INSERT INTO `crypto` (`name`, `symbol`, `subunit`, `fee`, `tradable`, `exchangeble`, `show_subunit`, `image_path`, `is_token`, `native_subunit`) 
            VALUES (\'Avalanche\', \'AVAX\', 18, 500000000000000000, 1, 1, 8, \'/media/default_avax.png\', 0, 18);');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DELETE FROM crypto WHERE name = \'Avalanche\';');
    }
}
