<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201013194255 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            'CREATE TABLE airdrop_action (
                id INT AUTO_INCREMENT NOT NULL,
                airdrop_id INT NOT NULL,
                type INT NOT NULL,
                data VARCHAR(255) DEFAULT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        $this->addSql('ALTER TABLE airdrop_action ADD CONSTRAINT FK_AirdropActions_Airdrops FOREIGN KEY (airdrop_id) REFERENCES airdrop(id)');

        $this->addSql(
            'CREATE TABLE airdrop_action_user (
                id INT AUTO_INCREMENT NOT NULL,
                airdrop_action_id INT NOT NULL,
                user_id INT NOT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        $this->addSql('ALTER TABLE airdrop_action_user ADD CONSTRAINT FK_AirdropActions_Users FOREIGN KEY (user_id) REFERENCES user(id)');
        $this->addSql('ALTER TABLE airdrop_action_user ADD CONSTRAINT FK_Users_AirdropActions FOREIGN KEY (airdrop_action_id) REFERENCES airdrop_action(id)');
        $this->addSql('CREATE UNIQUE INDEX UI_airdrp_action_user ON airdrop_action_user(airdrop_action_id, user_id)');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE airdrop_action_user DROP FOREIGN KEY FK_Users_AirdropActions');
        $this->addSql('ALTER TABLE airdrop_action_user DROP FOREIGN KEY FK_AirdropActions_Users');
        $this->addSql('DROP INDEX UI_airdrp_action_user ON airdrop_action_user');

        $this->addSql('DROP TABLE airdrop_action_user');

        $this->addSql('ALTER TABLE airdrop_action DROP FOREIGN KEY FK_AirdropActions_Airdrops');
        $this->addSql('DROP TABLE airdrop_action');
    }
}
