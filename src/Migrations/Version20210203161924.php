<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210203161924 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE scheduled_notifications ADD CONSTRAINT FK_shnotifications_users FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_FK_shnotifications_users ON scheduled_notifications (user_id)');

        $this->addSql('ALTER TABLE user_notifications ADD CONSTRAINT FK_unotifications_users FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_FK_unotifications_users ON user_notifications (user_id)');

        $this->addSql('ALTER TABLE user_notifications_config ADD CONSTRAINT FK_unotificationsconfig_users FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_FK_unotificationsconfig_users ON user_notifications_config (user_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE scheduled_notifications DROP FOREIGN KEY FK_shnotifications_users');
        $this->addSql('DROP INDEX IDX_FK_shnotifications_users ON scheduled_notifications');

        $this->addSql('ALTER TABLE user_notifications DROP FOREIGN KEY FK_unotifications_users');
        $this->addSql('DROP INDEX IDX_FK_unotifications_users ON user_notifications');

        $this->addSql('ALTER TABLE user_notifications_config DROP FOREIGN KEY FK_unotificationsconfig_users');
        $this->addSql('DROP INDEX IDX_FK_unotificationsconfig_users ON user_notifications_config');

    }
}
