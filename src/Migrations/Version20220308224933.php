<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220308224933 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('INSERT INTO cron_job (name, command, schedule, description, enabled)
            VALUE ("update-token-blacklist", "blacklist:synchronize", "0 0 * * *", "This command updates the forbidden token names", 1);');
    }
    
    public function down(Schema $schema) : void
    {
        $this->addSql('DELETE FROM cron_job WHERE name = "update-token-blacklist";');
    }
}
