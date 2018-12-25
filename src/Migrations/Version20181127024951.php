<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181127024951 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE crypto ADD subunit INT NOT NULL');
        $this->addSql('ALTER TABLE lock_in CHANGE amount_to_release amount_to_release BIGINT NOT NULL, CHANGE frozen_amount frozen_amount BIGINT NOT NULL');

        $this->addSql('UPDATE crypto SET subunit = 8 WHERE symbol = "BTC"');
        $this->addSql('UPDATE crypto SET subunit = 18 WHERE symbol = "WEB"');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE crypto DROP subunit');
        $this->addSql('ALTER TABLE lock_in CHANGE amount_to_release amount_to_release DOUBLE PRECISION NOT NULL, CHANGE frozen_amount frozen_amount DOUBLE PRECISION NOT NULL');
    }
}
