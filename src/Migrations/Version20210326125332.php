<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210326125332 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Translation fields for news content (title, abstract and content)';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE news__post ADD es_title VARCHAR(255) DEFAULT NULL, ADD ar_title VARCHAR(255) DEFAULT NULL, ADD fr_title VARCHAR(255) DEFAULT NULL, ADD pl_title VARCHAR(255) DEFAULT NULL, ADD pt_title VARCHAR(255) DEFAULT NULL, ADD ru_title VARCHAR(255) DEFAULT NULL, ADD ua_title VARCHAR(255) DEFAULT NULL, ADD es_abstract VARCHAR(255) DEFAULT NULL, ADD ar_abstract VARCHAR(255) DEFAULT NULL, ADD fr_abstract VARCHAR(255) DEFAULT NULL, ADD pl_abstract VARCHAR(255) DEFAULT NULL, ADD pt_abstract VARCHAR(255) DEFAULT NULL, ADD ru_abstract VARCHAR(255) DEFAULT NULL, ADD ua_abstract VARCHAR(255) DEFAULT NULL, ADD es_content VARCHAR(255) DEFAULT NULL, ADD ar_content VARCHAR(255) DEFAULT NULL, ADD fr_content VARCHAR(255) DEFAULT NULL, ADD pl_content VARCHAR(255) DEFAULT NULL, ADD pt_content VARCHAR(255) DEFAULT NULL, ADD ru_content VARCHAR(255) DEFAULT NULL, ADD ua_content VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE news__post DROP es_title, DROP ar_title, DROP fr_title, DROP pl_title, DROP pt_title, DROP ru_title, DROP ua_title, DROP es_abstract, DROP ar_abstract, DROP fr_abstract, DROP pl_abstract, DROP pt_abstract, DROP ru_abstract, DROP ua_abstract, DROP es_content, DROP ar_content, DROP fr_content, DROP pl_content, DROP pt_content, DROP ru_content, DROP ua_content');
    }
}
