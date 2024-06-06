<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211122121247 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }
    
    public function up(Schema $schema) : void
    {
        $this->addSql('INSERT INTO cron_job (name, command, schedule, description, enabled)
            VALUE ("synchronize-phones", "app:synchronize-phones", "0 0 * * *", "This command updates black List of phone numbers", 1);');
    }
    
    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('DELETE FROM cron_job WHERE name = "synchronize-phones";');
    }
}
