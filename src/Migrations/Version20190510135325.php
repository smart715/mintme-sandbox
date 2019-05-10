<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190510135325 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE market_status DROP FOREIGN KEY FK_5BA4CE10ABE44F28');
        $this->addSql('ALTER TABLE market_status ADD CONSTRAINT FK_5BA4CE10ABE44F28 FOREIGN KEY (quote_token_id) REFERENCES token (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE market_status DROP FOREIGN KEY FK_5BA4CE10ABE44F28');
        $this->addSql('ALTER TABLE market_status ADD CONSTRAINT FK_5BA4CE10ABE44F28 FOREIGN KEY (quote_token_id) REFERENCES token (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
