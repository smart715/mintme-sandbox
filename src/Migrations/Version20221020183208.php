<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221020183208 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE market_status 
            ADD change_percentage NUMERIC(16, 2) DEFAULT \'0\' NOT NULL, 
            ADD market_cap NUMERIC(65, 0) DEFAULT \'0\' NOT NULL, 
            ADD holders INT DEFAULT NULL, 
            ADD networks LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\'
        ');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE market_status 
            DROP change_percentage,
            DROP market_cap,
            DROP holders, 
            DROP networks
        ');
    }
}
