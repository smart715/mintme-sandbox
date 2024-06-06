<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221116090321 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Fix token cover image having index instead of unique index in O:O relation';
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE token DROP INDEX FK_5F37A13BE5A0E336, ADD UNIQUE INDEX UNIQ_5F37A13BE5A0E336 (cover_image_id)');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE token DROP INDEX UNIQ_5F37A13BE5A0E336, ADD INDEX FK_5F37A13BE5A0E336 (cover_image_id)');
    }
}
