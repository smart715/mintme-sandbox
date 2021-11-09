<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200722142528 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE token ADD number_of_reminder SMALLINT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE token ADD next_reminder_date DATE DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {

        $this->addSql('ALTER TABLE token DROP number_of_reminder, DROP next_reminder_date');

    }
}
