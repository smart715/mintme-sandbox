<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220928075106 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'create rank_id_sequence sequence and add rank_id column to token table';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql("CREATE SEQUENCE IF NOT EXISTS rank_id_sequence START WITH 1 INCREMENT BY 1");
        $this->addSql("ALTER TABLE token ADD COLUMN IF NOT EXISTS rank_id INT DEFAULT (NEXT VALUE FOR rank_id_sequence)");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql("ALTER TABLE token DROP COLUMN rank_id");
        $this->addSql("DROP SEQUENCE rank_id_sequence");
    }
}
