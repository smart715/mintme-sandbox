<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190924142525 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('INSERT INTO cron_job (name, command, schedule, description, enabled) 
            VALUE ("synchronize-domains", "app:synchronize-domains", "* * * * *", "This command updates black List of domains", 1);');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('DELETE FROM cron_job WHERE name = "synchronize-domains";');
    }
}
