<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220825112511 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE user_tokens ADD is_referral TINYINT(1) DEFAULT \'0\' NOT NULL');

    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE user_tokens DROP is_referral');

    }
}
