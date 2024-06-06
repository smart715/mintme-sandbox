<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240214193301 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Fix doctrine:migration:diff';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE google_authenticator_entry CHANGE backup_codes_downloads backup_codes_downloads INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE token CHANGE token_proposal_min_amount token_proposal_min_amount VARCHAR(255) DEFAULT \'1000\' NOT NULL, CHANGE dm_min_amount dm_min_amount VARCHAR(255) DEFAULT \'1000\' NOT NULL');
        $this->addSql('ALTER TABLE token_signup_history CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE user_change_email_request DROP FOREIGN KEY FK_2491F396A76ED395');
        $this->addSql('ALTER TABLE user_change_email_request ADD CONSTRAINT FK_2491F396A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE news__post CHANGE de_abstract de_abstract LONGTEXT DEFAULT NULL, CHANGE de_content de_content LONGTEXT DEFAULT NULL');

    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE google_authenticator_entry CHANGE backup_codes_downloads backup_codes_downloads INT DEFAULT 0');
        $this->addSql('ALTER TABLE token CHANGE token_proposal_min_amount token_proposal_min_amount VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'100\' NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE dm_min_amount dm_min_amount VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'100\' NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE token_signup_history CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE user_change_email_request DROP FOREIGN KEY FK_2491F396A76ED395');
        $this->addSql('ALTER TABLE user_change_email_request ADD CONSTRAINT FK_2491F396A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE news__post CHANGE de_abstract de_abstract VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE de_content de_content VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
