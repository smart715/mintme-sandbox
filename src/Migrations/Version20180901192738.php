<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180901192738 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE token ADD profile_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE token ADD CONSTRAINT FK_5F37A13BCCFA12B8 FOREIGN KEY (profile_id) REFERENCES profile (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5F37A13BCCFA12B8 ON token (profile_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE token DROP FOREIGN KEY FK_5F37A13BCCFA12B8');
        $this->addSql('DROP INDEX UNIQ_5F37A13BCCFA12B8 ON token');
        $this->addSql('ALTER TABLE token DROP profile_id');
    }
}
