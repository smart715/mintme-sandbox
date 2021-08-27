<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201018130941 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'This file modifies and adds a profile creating date to the profile table.';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE profile ADD created DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql("ALTER TABLE profile DROP created");
    }
}
