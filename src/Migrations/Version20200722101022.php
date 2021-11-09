<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200722101022 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE profile ADD number_of_reminder SMALLINT NOT NULL, ADD next_reminder_date DATE DEFAULT NULL');

        // this up() migration is auto-generated, please modify it to your needs

    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE profile DROP number_of_reminder, DROP next_reminder_date');

        // this down() migration is auto-generated, please modify it to your needs

    }
}
