<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231204183441 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Set 18 subunits for USDC and BTC by default, since all values were migrated to 18 subunits on prod';
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('UPDATE crypto SET subunit = 18 WHERE symbol = \'BTC\';');
        $this->addSql('UPDATE crypto SET subunit = 18 WHERE symbol = \'USDC\';');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('UPDATE crypto SET subunit = 8 WHERE symbol = \'BTC\';');
        $this->addSql('UPDATE crypto SET subunit = 6 WHERE symbol = \'USDC\';');    }
}
