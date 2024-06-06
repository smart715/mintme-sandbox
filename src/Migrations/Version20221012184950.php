<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221012184950 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Cascade delete airdrop on token deletion';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('ALTER TABLE airdrop DROP FOREIGN KEY FK_4D15AF2C41DEE7B9');
        $this->addSql('ALTER TABLE airdrop ADD CONSTRAINT FK_4D15AF2C41DEE7B9 FOREIGN KEY (token_id) REFERENCES token (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('ALTER TABLE airdrop DROP FOREIGN KEY FK_4D15AF2C41DEE7B9');
        $this->addSql('ALTER TABLE airdrop ADD CONSTRAINT FK_4D15AF2C41DEE7B9 FOREIGN KEY (token_id) REFERENCES token (id)');
    }
}