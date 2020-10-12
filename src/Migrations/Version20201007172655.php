<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201007172655 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('INSERT INTO cron_job (name, command, schedule, description, enabled)
            VALUE ("app:profile-creating-reminder", "app:profile-creating-reminder", "0 0 * * *", "Send profile description reminders", 1);');

        $this->addSql('INSERT INTO cron_job (name, command, schedule, description, enabled)
            VALUE ("app:token-description-reminder", "app:token-description-reminder", "0 0 * * *", "Send token description reminders", 1);');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DELETE FROM cron_job WHERE name = "app:profile-creating-reminder";');

        $this->addSql('DELETE FROM cron_job WHERE name = "app:token-description-reminder";');
    }
}
