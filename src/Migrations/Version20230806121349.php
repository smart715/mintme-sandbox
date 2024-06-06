<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230806121349 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add index to user_notifications table date column';
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE INDEX IDX_NOTIFICATION_DATE ON user_notifications (date)');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('DROP INDEX IDX_NOTIFICATION_DATE ON user_notifications');
    }
}
