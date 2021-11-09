<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210217213305 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add column airdrop_referrer_id to user table';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE user ADD COLUMN airdrop_referrer_user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD COLUMN airdrop_referrer_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_AirdropReferrerUser FOREIGN KEY (airdrop_referrer_user_id) REFERENCES user(id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_AirdropReferrer FOREIGN KEY (airdrop_referrer_id) REFERENCES airdrop(id) ON DELETE SET NULL');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_AirdropReferrer');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_AirdropReferrerUser');
        $this->addSql('ALTER TABLE user DROP airdrop_referrer_id');
        $this->addSql('ALTER TABLE user DROP airdrop_referrer_user_id');
    }
}
