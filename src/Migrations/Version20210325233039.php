<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210325233039 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE airdrop_participants DROP FOREIGN KEY FK_BC712FC513543E34');
        $this->addSql('ALTER TABLE airdrop_participants ADD CONSTRAINT FK_BC712FC513543E34 FOREIGN KEY (airdrop_id) REFERENCES airdrop(id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE airdrop_participants DROP FOREIGN KEY FK_BC712FC5A76ED395');
        $this->addSql('ALTER TABLE airdrop_participants ADD CONSTRAINT FK_BC712FC5A76ED395 FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE airdrop_action_user DROP FOREIGN KEY FK_Users_AirdropActions');
        $this->addSql('ALTER TABLE airdrop_action_user ADD CONSTRAINT FK_Users_AirdropActions FOREIGN KEY (airdrop_action_id) REFERENCES airdrop_action(id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE airdrop_action_user DROP FOREIGN KEY FK_AirdropActions_Users');
        $this->addSql('ALTER TABLE airdrop_action_user ADD CONSTRAINT FK_AirdropActions_Users FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE airdrop_action DROP FOREIGN KEY FK_AirdropActions_Airdrops');
        $this->addSql('ALTER TABLE airdrop_action ADD CONSTRAINT FK_AirdropActions_Airdrops FOREIGN KEY (airdrop_id) REFERENCES airdrop(id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE airdrop_action DROP FOREIGN KEY FK_AirdropActions_Airdrops');
        $this->addSql('ALTER TABLE airdrop_action ADD CONSTRAINT FK_AirdropActions_Airdrops FOREIGN KEY (airdrop_id) REFERENCES airdrop(id)');

        $this->addSql('ALTER TABLE airdrop_action_user DROP FOREIGN KEY FK_Users_AirdropActions');
        $this->addSql('ALTER TABLE airdrop_action_user ADD CONSTRAINT FK_Users_AirdropActions FOREIGN KEY (airdrop_action_id) REFERENCES airdrop_action(id)');

        $this->addSql('ALTER TABLE airdrop_action_user DROP FOREIGN KEY FK_AirdropActions_Users');
        $this->addSql('ALTER TABLE airdrop_action_user ADD CONSTRAINT FK_AirdropActions_Users FOREIGN KEY (user_id) REFERENCES user(id)');

        $this->addSql('ALTER TABLE airdrop_participants DROP FOREIGN KEY FK_BC712FC5A76ED395');
        $this->addSql('ALTER TABLE airdrop_participants ADD CONSTRAINT FK_BC712FC5A76ED395 FOREIGN KEY (user_id) REFERENCES user(id)');

        $this->addSql('ALTER TABLE airdrop_participants DROP FOREIGN KEY FK_BC712FC513543E34');
        $this->addSql('ALTER TABLE airdrop_participants ADD CONSTRAINT FK_BC712FC513543E34 FOREIGN KEY (airdrop_id) REFERENCES airdrop(id)');
    }
}
