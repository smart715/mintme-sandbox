<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210621191111 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Long translation fields for news content ( abstract and content)';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE news__post CHANGE es_abstract es_abstract LONGTEXT DEFAULT NULL, CHANGE ar_abstract ar_abstract LONGTEXT DEFAULT NULL, CHANGE fr_abstract fr_abstract LONGTEXT DEFAULT NULL, CHANGE pl_abstract pl_abstract LONGTEXT DEFAULT NULL, CHANGE pt_abstract pt_abstract LONGTEXT DEFAULT NULL, CHANGE ru_abstract ru_abstract LONGTEXT DEFAULT NULL, CHANGE ua_abstract ua_abstract LONGTEXT DEFAULT NULL, CHANGE es_content es_content LONGTEXT DEFAULT NULL, CHANGE ar_content ar_content LONGTEXT DEFAULT NULL, CHANGE fr_content fr_content LONGTEXT DEFAULT NULL, CHANGE pl_content pl_content LONGTEXT DEFAULT NULL, CHANGE pt_content pt_content LONGTEXT DEFAULT NULL, CHANGE ru_content ru_content LONGTEXT DEFAULT NULL, CHANGE ua_content ua_content LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE news__post CHANGE es_abstract es_abstract VARCHAR(255) DEFAULT NULL, CHANGE ar_abstract ar_abstract VARCHAR(255) DEFAULT NULL, CHANGE fr_abstract fr_abstract VARCHAR(255) DEFAULT NULL, CHANGE pl_abstract pl_abstract VARCHAR(255) DEFAULT NULL, CHANGE pt_abstract pt_abstract VARCHAR(255) DEFAULT NULL, CHANGE ru_abstract ru_abstract VARCHAR(255) DEFAULT NULL, CHANGE ua_abstract ua_abstract VARCHAR(255) DEFAULT NULL, CHANGE es_content es_content VARCHAR(255) DEFAULT NULL, CHANGE ar_content ar_content VARCHAR(255) DEFAULT NULL, CHANGE fr_content fr_content VARCHAR(255) DEFAULT NULL, CHANGE pl_content pl_content VARCHAR(255) DEFAULT NULL, CHANGE pt_content pt_content VARCHAR(255) DEFAULT NULL, CHANGE ru_content ru_content VARCHAR(255) DEFAULT NULL, CHANGE ua_content ua_content VARCHAR(255) DEFAULT NULL');
    }
}
