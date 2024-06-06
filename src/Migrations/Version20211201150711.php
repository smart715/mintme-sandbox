<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211201150711 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('ALTER TABLE reward_volunteers DROP FOREIGN KEY FK_459E1FD4E466ACA1');
        $this->addSql('ALTER TABLE reward_volunteers ADD CONSTRAINT FK_459E1FD4E466ACA1 FOREIGN KEY (reward_id) REFERENCES reward (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reward_participants DROP FOREIGN KEY FK_19A15687E466ACA1');
        $this->addSql('ALTER TABLE reward_participants ADD CONSTRAINT FK_19A15687E466ACA1 FOREIGN KEY (reward_id) REFERENCES reward (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('ALTER TABLE reward_participants DROP FOREIGN KEY FK_19A15687E466ACA1');
        $this->addSql('ALTER TABLE reward_volunteers DROP FOREIGN KEY FK_459E1FD4E466ACA1');
        
    }
}
