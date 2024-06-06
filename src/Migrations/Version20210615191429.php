<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210615191429 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('ALTER TABLE activity ADD reward_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE activity ADD user3_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE activity ADD is_bounty INT DEFAULT NULL');
        $this->addSql('ALTER TABLE activity ADD CONSTRAINT FK_Activities_rewards FOREIGN KEY (reward_id) REFERENCES reward (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE activity ADD CONSTRAINT FK_Activities_users_3 FOREIGN KEY (user3_id) REFERENCES user (id) ON DELETE CASCADE');

    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('ALTER TABLE activity DROP FOREIGN KEY FK_Activities_rewards');
        $this->addSql('ALTER TABLE activity DROP FOREIGN KEY FK_Activities_users_3');
        $this->addSql('ALTER TABLE activity DROP reward_id');
        $this->addSql('ALTER TABLE activity DROP is_bounty');
    }
}
