<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220519122804 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'make default createdAt prop "timestamp(0)" instead current timestamp';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE reward_participants MODIFY created_at DATETIME NOT NULL DEFAULT TIMESTAMP(0)');
        $this->addSql('ALTER TABLE airdrop_participants MODIFY created_at DATETIME NOT NULL DEFAULT TIMESTAMP(0)');
        $this->addSql('ALTER TABLE post_users_share_reward MODIFY created_at DATETIME NOT NULL DEFAULT TIMESTAMP(0)');

    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE reward_participants MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE airdrop_participants MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE post_users_share_reward MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
    }
}
