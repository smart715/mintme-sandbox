<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220305084352 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Remove Blacklisted Ip from database';
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('INSERT INTO cron_job (name, command, schedule, description, enabled)
            VALUE ("remove-blacklisted-ip", "app:remove-blacklisted-ip", "0 0 * * *", "Remove Blacklisted Ip from database", 1);');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DELETE FROM cron_job WHERE name = "remove-blacklisted-ip";');
    }
}
