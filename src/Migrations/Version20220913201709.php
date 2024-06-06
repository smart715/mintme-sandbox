<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220913201709 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Change airdrop_participant_index unique constraint to allow only one referral per user and airdrop';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('ALTER TABLE airdrop_participants DROP FOREIGN KEY FK_BC712FC513543E34');
        $this->addSql('ALTER TABLE airdrop_participants DROP FOREIGN KEY FK_BC712FC5A76ED395');
        $this->addSql('DROP INDEX airdrop_participant_index ON airdrop_participants');
        $this->addSql('ALTER TABLE airdrop_participants ADD referral_id INT DEFAULT NULL, DROP referral');
        $this->addSql('ALTER TABLE airdrop_participants ADD CONSTRAINT FK_BC712FC53CCAA4B7 FOREIGN KEY (referral_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE airdrop_participants ADD CONSTRAINT FK_BC712FC513543E34 FOREIGN KEY (airdrop_id) REFERENCES airdrop (id)');
        $this->addSql('ALTER TABLE airdrop_participants ADD CONSTRAINT FK_BC712FC5A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_BC712FC53CCAA4B7 ON airdrop_participants (referral_id)');
        $this->addSql('CREATE UNIQUE INDEX airdrop_participant_index ON airdrop_participants (user_id, airdrop_id, referral_id)');;
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('ALTER TABLE airdrop_participants DROP FOREIGN KEY FK_BC712FC53CCAA4B7');
        $this->addSql('ALTER TABLE airdrop_participants DROP FOREIGN KEY FK_BC712FC513543E34');
        $this->addSql('ALTER TABLE airdrop_participants DROP FOREIGN KEY FK_BC712FC5A76ED395');
        $this->addSql('DROP INDEX IDX_BC712FC53CCAA4B7 ON airdrop_participants');
        $this->addSql('DROP INDEX airdrop_participant_index ON airdrop_participants');
        $this->addSql('ALTER TABLE airdrop_participants ADD referral TINYINT(1) DEFAULT \'0\' NOT NULL, DROP referral_id');
        $this->addSql('ALTER TABLE airdrop_participants ADD CONSTRAINT FK_BC712FC513543E34 FOREIGN KEY (airdrop_id) REFERENCES airdrop (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE airdrop_participants ADD CONSTRAINT FK_BC712FC5A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('CREATE UNIQUE INDEX airdrop_participant_index ON airdrop_participants (user_id, airdrop_id)');
    }
}
