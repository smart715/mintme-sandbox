<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210218215300 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Create airdrop_referrer table';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('CREATE TABLE airdrop_referral_code (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, airdrop_id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        $this->addSql('ALTER TABLE airdrop_referral_code ADD CONSTRAINT FK_AirdropReferralCode_User FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE airdrop_referral_code ADD CONSTRAINT FK_AirdropReferralCode_Airdrops FOREIGN KEY (airdrop_id) REFERENCES airdrop(id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE airdrop_referral_code DROP FOREIGN KEY FK_AirdropReferralCode_Airdrops');

        $this->addSql('ALTER TABLE airdrop_referral_code DROP FOREIGN KEY FK_AirdropReferralCode_User');

        $this->addSql('DROP TABLE airdrop_referral_code');
    }
}
