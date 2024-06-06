<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211021173527 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Created on mintme site property. Update this property for tokens created not on mintme site';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE token ADD created_on_mintme_site TINYINT(1) DEFAULT \'1\' NOT NULL');
        $this->addSql('UPDATE token SET created_on_mintme_site=false WHERE deployed=true AND tx_hash IS NULL AND deploy_cost IS NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE token DROP created_on_mintme_site');
    }
}
