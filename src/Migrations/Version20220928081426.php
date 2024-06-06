<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220928081426 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'create token_rank view';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql("
            CREATE OR REPLACE VIEW view_token_rank AS
            SELECT
                t.rank_id id,
                RANK() OVER (ORDER BY t.total_volume DESC, t.token_id ASC) `rank`
            FROM view_token_total_volume t;
        ");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('DROP VIEW IF EXISTS token_rank');
    }
}
