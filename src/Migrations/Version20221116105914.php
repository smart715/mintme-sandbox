<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221116105914 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Fix incorrect index for O:O relationship';
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE deploy_token_reward DROP FOREIGN KEY FK_4122B1B4A76ED395');
        $this->addSql('DROP INDEX idx_4122b1b4a76ed395 ON deploy_token_reward');
        $this->addSql('CREATE INDEX IDX_E903543BA76ED395 ON deploy_token_reward (user_id)');
        $this->addSql('ALTER TABLE deploy_token_reward ADD CONSTRAINT FK_4122B1B4A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE deploy_token_reward DROP FOREIGN KEY FK_4122B1B4A76ED395');
        $this->addSql('DROP INDEX idx_e903543ba76ed395 ON deploy_token_reward');
        $this->addSql('CREATE INDEX IDX_4122B1B4A76ED395 ON deploy_token_reward (user_id)');
        $this->addSql('ALTER TABLE deploy_token_reward ADD CONSTRAINT FK_4122B1B4A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }
}
