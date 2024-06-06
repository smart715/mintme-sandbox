<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221116065551 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Fix airdrop_action_user M:M table names to doctrine naming convention';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE airdrop_action_user MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE airdrop_action_user DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE airdrop_action_user DROP FOREIGN KEY FK_AirdropActions_Users');
        $this->addSql('ALTER TABLE airdrop_action_user DROP id');
        $this->addSql('ALTER TABLE airdrop_action_user ADD PRIMARY KEY (airdrop_action_id, user_id)');
        $this->addSql('DROP INDEX fk_airdropactions_users ON airdrop_action_user');
        $this->addSql('CREATE INDEX IDX_342BA9B2A76ED395 ON airdrop_action_user (user_id)');
        $this->addSql('ALTER TABLE airdrop_action_user ADD CONSTRAINT FK_AirdropActions_Users FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX UI_airdrp_action_user ON airdrop_action_user');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE airdrop_action_user DROP FOREIGN KEY FK_AirdropActions_Users');
        $this->addSql('DROP INDEX idx_342ba9b2a76ed395 ON airdrop_action_user');
        $this->addSql('ALTER TABLE airdrop_action_user ADD CONSTRAINT FK_342BA9B2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX FK_AirdropActions_Users ON airdrop_action_user (user_id)');
        $this->addSql('CREATE UNIQUE INDEX UI_airdrp_action_user ON airdrop_action_user (airdrop_action_id, user_id)');
        $this->addSql('ALTER TABLE airdrop_action_user ADD id INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
    }
}
