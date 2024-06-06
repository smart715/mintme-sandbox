<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220531063016 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'add status to post and reward, add referral boolean prop to airdrop_participants';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE airdrop_participants ADD referral BOOLEAN NOT NULL DEFAULT false');
        $this->addSql('ALTER TABLE post ADD status INTEGER NOT NULL DEFAULT 1');
        $this->addSql('ALTER TABLE reward ADD status INTEGER NOT NULL DEFAULT 1');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE airdrop_participants DROP refferal');
        $this->addSql('ALTER TABLE post DROP status');
        $this->addSql('ALTER TABLE reward DROP status');
    }
}
