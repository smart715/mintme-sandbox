<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240219232610 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add referencer_id and referencer_amount to donation';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE donation ADD referencer_id INT, ADD referencer_amount VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE donation ADD CONSTRAINT FK_31E581A0F0D184D2 FOREIGN KEY (referencer_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_31E581A0F0D184D2 ON donation (referencer_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE donation DROP FOREIGN KEY FK_31E581A0F0D184D2');
        $this->addSql('DROP INDEX IDX_31E581A0F0D184D2 ON donation');
        $this->addSql('ALTER TABLE donation DROP referencer_id, DROP referencer_amount');
    }
}
