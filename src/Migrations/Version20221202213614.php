<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221202213614 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE knowledge_base ADD de_title VARCHAR(255) DEFAULT NULL, ADD de_description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE knowledge_base_subcategory ADD de_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE knowledge_base_category ADD de_name VARCHAR(255) DEFAULT NULL');

        $this->addSql('ALTER TABLE news__post ADD de_title VARCHAR(255) DEFAULT NULL, ADD de_abstract VARCHAR(255) DEFAULT NULL, ADD de_content VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE knowledge_base DROP de_title, DROP de_description');
        $this->addSql('ALTER TABLE knowledge_base_category DROP de_name');
        $this->addSql('ALTER TABLE knowledge_base_subcategory DROP de_name');

        $this->addSql('ALTER TABLE news__post DROP de_title, DROP de_abstract, DROP de_content');
    }
}
