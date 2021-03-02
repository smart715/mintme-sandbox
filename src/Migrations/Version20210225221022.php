<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210225221022 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user_login_info ADD CONSTRAINT FK_AA9C7CFEA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_AA9C7CFEA76ED395 ON user_login_info (user_id)');
        $this->addSql('CREATE UNIQUE INDEX user_ip_unique ON user_login_info (user_id, ip_address)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user_login_info DROP FOREIGN KEY FK_AA9C7CFEA76ED395');
        $this->addSql('DROP INDEX IDX_AA9C7CFEA76ED395 ON user_login_info');
        $this->addSql('DROP INDEX user_ip_unique ON user_login_info');
    }
}
