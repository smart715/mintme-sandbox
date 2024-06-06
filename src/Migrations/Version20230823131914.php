<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230823131914 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'modify token_rank view rank calculation';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql("
            CREATE OR REPLACE VIEW view_token_total_volume AS
            SELECT
                t.id token_id,
                t.rank_id rank_id,
                SUM((ms.month_volume / POW(10, c.subunit)) * c.usd_exchange_rate) total_volume
            FROM token t
            INNER JOIN market_status ms ON t.id = ms.quote_token_id
            INNER JOIN crypto c on ms.crypto_id = c.id
            WHERE t.deployed = 1 AND t.is_hidden = 0
            GROUP BY t.id
        ");

    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql("
            CREATE OR REPLACE VIEW view_token_total_volume AS
            SELECT
                t.id token_id,
                t.rank_id rank_id,
                SUM((ms.month_volume / POW(10, c.subunit)) * (c.usd_exchange_rate / 100)) total_volume
            FROM token t
            INNER JOIN market_status ms ON t.id = ms.quote_token_id
            INNER JOIN crypto c on ms.crypto_id = c.id
            WHERE t.deployed = 1 AND t.is_hidden = 0
            GROUP BY t.id
        ");
    }
}
