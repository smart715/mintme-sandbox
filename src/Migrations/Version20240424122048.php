<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240424122048 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Recreate activity';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE activity DROP FOREIGN KEY FK_AC74095A4B89032C');
        $this->addSql('ALTER TABLE activity DROP FOREIGN KEY FK_AC74095AE9571A63');
        $this->addSql('ALTER TABLE activity DROP FOREIGN KEY FK_Activities_Tokens');
        $this->addSql('ALTER TABLE activity DROP FOREIGN KEY FK_Activities_rewards');
        $this->addSql('ALTER TABLE activity DROP FOREIGN KEY IDX_AC74095A441B8B65');
        $this->addSql('ALTER TABLE activity DROP FOREIGN KEY IDX_AC74095A56AE248B');
        $this->addSql('ALTER TABLE activity DROP FOREIGN KEY IDX_AC74095AFCA7EC00');
        $this->addSql('delete from activity');
        $this->addSql('ALTER TABLE activity ADD context LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', DROP token_id, DROP user1_id, DROP user2_id, DROP post_id, DROP reward_id, DROP user3_id, DROP crypto_id, DROP amount, DROP currency, DROP is_bounty, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE activity ADD token_id INT NOT NULL, ADD user1_id INT DEFAULT NULL, ADD user2_id INT DEFAULT NULL, ADD post_id INT DEFAULT NULL, ADD reward_id INT DEFAULT NULL, ADD user3_id INT DEFAULT NULL, ADD crypto_id INT DEFAULT NULL, ADD amount VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD currency VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD is_bounty TINYINT(1) DEFAULT NULL, DROP context, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE activity ADD CONSTRAINT FK_AC74095A4B89032C FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE activity ADD CONSTRAINT FK_AC74095AE9571A63 FOREIGN KEY (crypto_id) REFERENCES crypto (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE activity ADD CONSTRAINT FK_Activities_Tokens FOREIGN KEY (token_id) REFERENCES token (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE activity ADD CONSTRAINT FK_Activities_rewards FOREIGN KEY (reward_id) REFERENCES reward (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE activity ADD CONSTRAINT IDX_AC74095A441B8B65 FOREIGN KEY (user2_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE activity ADD CONSTRAINT IDX_AC74095A56AE248B FOREIGN KEY (user1_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE activity ADD CONSTRAINT IDX_AC74095AFCA7EC00 FOREIGN KEY (user3_id) REFERENCES user (id) ON DELETE CASCADE');
    }
}
