<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230605063138 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'cut extra 6 zeroes from tok amounts in donations';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('UPDATE donation SET token_amount = IF(LENGTH(token_amount) <= 6, \'0\', SUBSTRING(token_amount, 1, LENGTH(token_amount) - 6))');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('UPDATE donation SET token_amount = IF(token_amount = \'0\', \'0\', CONCAT(token_amount, \'000000\'))');
    }
}
