<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210323205235 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Knowledge Base translation fields for title and description (es, ar, fr, pl, pt, ru, ua)';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE knowledge_base ADD es_title VARCHAR(255) DEFAULT NULL, ADD ar_title VARCHAR(255) DEFAULT NULL, ADD fr_title VARCHAR(255) DEFAULT NULL, ADD pl_title VARCHAR(255) DEFAULT NULL, ADD pt_title VARCHAR(255) DEFAULT NULL, ADD ru_title VARCHAR(255) DEFAULT NULL, ADD ua_title VARCHAR(255) DEFAULT NULL, ADD es_description LONGTEXT DEFAULT NULL, ADD ar_description LONGTEXT DEFAULT NULL, ADD fr_description LONGTEXT DEFAULT NULL, ADD pl_description LONGTEXT DEFAULT NULL, ADD pt_description LONGTEXT DEFAULT NULL, ADD ru_description LONGTEXT DEFAULT NULL, ADD ua_description LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE knowledge_base DROP es_title, DROP ar_title, DROP fr_title, DROP pl_title, DROP pt_title, DROP ru_title, DROP ua_title, DROP es_description, DROP ar_description, DROP fr_description, DROP pl_description, DROP pt_description, DROP ru_description, DROP ua_description');
    }
}
