<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201223204647 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('UPDATE cron_job 
                                set command = "app:check-scheduled-notifications", 
                                    name = "scheduled-notifications",
                                    description = "Checks the notification scheduled to be sent to the user"
                                    where name = "check-user-sell-orders";'
        );

    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('UPDATE cron_job 
                                set command = "app:check-user-sell-orders", 
                                    name = "check-user-sell-orders",
                                    description = "Checks the notification scheduled to be sent to the user"
                                    where name = "scheduled-notifications";'
        );

    }
}
