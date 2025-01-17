<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190410203249 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE pending_withdraw ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pending_withdraw ADD CONSTRAINT FK_321D93BBA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_321D93BBA76ED395 ON pending_withdraw (user_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE pending_withdraw DROP FOREIGN KEY FK_321D93BBA76ED395');
        $this->addSql('DROP INDEX IDX_321D93BBA76ED395 ON pending_withdraw');
        $this->addSql('ALTER TABLE pending_withdraw DROP user_id');
    }
}
