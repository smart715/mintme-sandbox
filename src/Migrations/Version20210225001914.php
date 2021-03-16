<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210225001914 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Change column airdrop.actual_participants from int to float)';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE airdrop MODIFY actual_participants FLOAT DEFAULT 0');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE airdrop MODIFY actual_participants INT DEFAULT 0');
    }
}
