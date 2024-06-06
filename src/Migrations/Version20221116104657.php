<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221116104657 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Fix incorrect index for O:O relationship, fix datetime column type';
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE auth_attempts DROP INDEX FK_5SS2MZ6JZZ4YM4VB, ADD UNIQUE INDEX UNIQ_9B3D60E2A76ED395 (user_id)');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE auth_attempts DROP INDEX UNIQ_9B3D60E2A76ED395, ADD INDEX FK_5SS2MZ6JZZ4YM4VB (user_id)');
    }
}
