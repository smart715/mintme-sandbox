<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210324223338 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('UPDATE token SET deployed = NULL WHERE deployed IS NOT NULL AND address IS NULL');
        $this->addSql("ALTER TABLE token CHANGE deployed deployed_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
        $this->addSql('ALTER TABLE token ADD COLUMN deployed TINYINT(1) NOT NULL DEFAULT 0 AFTER deployed_date');
        $this->addSql('UPDATE token SET deployed = CASE WHEN deployed_date IS NOT NULL THEN 1 ELSE 0 END');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE token DROP COLUMN deployed');
        $this->addSql("ALTER TABLE token CHANGE deployed_date deployed DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
    }
}
