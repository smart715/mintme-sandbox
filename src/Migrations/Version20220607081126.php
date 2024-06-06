<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220607081126 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE reward DROP IF EXISTS link;');
        $this->addSql('ALTER TABLE reward_volunteers ADD created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;');
        $this->addSql('ALTER TABLE reward_participants ADD bonus_price VARCHAR(255) DEFAULT NULL;');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE reward ADD link VARCHAR(255) DEFAULT NULL;');
        $this->addSql('ALTER TABLE reward_volunteers DROP created_at;');
        $this->addSql('ALTER TABLE reward_participants DROP bonus_price;');
    }
}
