<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221115105351 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Fix activity table IDX/FK names to doctrine auto-generated names';
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE activity DROP FOREIGN KEY FK_Activities_Users_1');
        $this->addSql('ALTER TABLE activity DROP FOREIGN KEY FK_Activities_Users_2');
        $this->addSql('ALTER TABLE activity DROP FOREIGN KEY FK_Activities_users_3');
        $this->addSql('ALTER TABLE activity ADD CONSTRAINT IDX_AC74095A56AE248B FOREIGN KEY (user1_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE activity ADD CONSTRAINT IDX_AC74095A441B8B65 FOREIGN KEY (user2_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE activity ADD CONSTRAINT IDX_AC74095AFCA7EC00 FOREIGN KEY (user3_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE activity RENAME INDEX fk_activities_tokens TO IDX_AC74095A41DEE7B9');
        $this->addSql('ALTER TABLE activity RENAME INDEX fk_ac74095a4b89032c TO IDX_AC74095A4B89032C');
        $this->addSql('ALTER TABLE activity RENAME INDEX fk_activities_rewards TO IDX_AC74095AE466ACA1');
        $this->addSql('ALTER TABLE activity CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE is_bounty is_bounty TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE activity DROP FOREIGN KEY IDX_AC74095A56AE248B');
        $this->addSql('ALTER TABLE activity DROP FOREIGN KEY IDX_AC74095A441B8B65');
        $this->addSql('ALTER TABLE activity DROP FOREIGN KEY IDX_AC74095AFCA7EC00');
        $this->addSql('ALTER TABLE activity ADD CONSTRAINT FK_Activities_Users_1 FOREIGN KEY (user1_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE activity ADD CONSTRAINT FK_Activities_Users_2 FOREIGN KEY (user2_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE activity ADD CONSTRAINT FK_Activities_users_3 FOREIGN KEY (user3_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE activity RENAME INDEX idx_ac74095a4b89032c TO FK_AC74095A4B89032C');
        $this->addSql('ALTER TABLE activity RENAME INDEX idx_ac74095a41dee7b9 TO FK_Activities_Tokens');
        $this->addSql('ALTER TABLE activity RENAME INDEX idx_ac74095ae466aca1 TO FK_Activities_rewards');
        $this->addSql('ALTER TABLE activity CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE is_bounty is_bounty INT DEFAULT NULL');
    }
}
