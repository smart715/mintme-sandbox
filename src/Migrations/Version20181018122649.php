<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181018122649 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('INSERT INTO cron_job (name, command, schedule, description, enabled) 
            VALUE ("update-token-release", "app:update-token-release", "0 * * * *", "This command updates statistic", 1);');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('DELETE FROM cron_job WHERE name = "update-token-release";');
    }
}
