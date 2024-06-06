<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231030174557 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'app:trade-inactive-accounts-assets cron job';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('INSERT INTO cron_job (name, command, schedule, description, enabled)
            VALUE ("app:trade-inactive-accounts-assets", "app:trade-inactive-accounts-assets", "15 9 * * *", "Trade inactive users assets into mintme", 1);');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DELETE FROM cron_job WHERE name = "app:trade-inactive-accounts-assets"');
    }
}
