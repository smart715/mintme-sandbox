<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220923204255 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'add field for easily limit backupcodes downloads';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE google_authenticator_entry ADD backup_codes_downloads INT DEFAULT 0, ADD lastdownload_backup_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE google_authenticator_entry DROP backup_codes_downloads, DROP lastdownload_backup_date');
    }
}
