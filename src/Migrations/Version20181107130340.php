<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181107130340 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('INSERT INTO crypto (name, symbol) VALUES (\'Bitcoin\', \'BTC\')');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('DROP FROM crypto WHERE name = \'Bitcoin\'');
    }
}
