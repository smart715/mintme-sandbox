<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210326190740 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Translation fields for kb category and subcategory';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE knowledge_base_subcategory ADD es_name VARCHAR(255) DEFAULT NULL, ADD ar_name VARCHAR(255) DEFAULT NULL, ADD fr_name VARCHAR(255) DEFAULT NULL, ADD pl_name VARCHAR(255) DEFAULT NULL, ADD pt_name VARCHAR(255) DEFAULT NULL, ADD ru_name VARCHAR(255) DEFAULT NULL, ADD ua_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE knowledge_base_category ADD es_name VARCHAR(255) DEFAULT NULL, ADD ar_name VARCHAR(255) DEFAULT NULL, ADD fr_name VARCHAR(255) DEFAULT NULL, ADD pl_name VARCHAR(255) DEFAULT NULL, ADD pt_name VARCHAR(255) DEFAULT NULL, ADD ru_name VARCHAR(255) DEFAULT NULL, ADD ua_name VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE knowledge_base_category DROP es_name, DROP ar_name, DROP fr_name, DROP pl_name, DROP pt_name, DROP ru_name, DROP ua_name');
        $this->addSql('ALTER TABLE knowledge_base_subcategory DROP es_name, DROP ar_name, DROP fr_name, DROP pl_name, DROP pt_name, DROP ru_name, DROP ua_name');
    }
}
