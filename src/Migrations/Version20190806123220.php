<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190806123220 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE knowledge_base_category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE knowledge_base_subcategory (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE knowledge_base (id INT AUTO_INCREMENT NOT NULL, category_id INT DEFAULT NULL, subcategory_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, url VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, INDEX IDX_AF81B45512469DE2 (category_id), INDEX IDX_AF81B4555DC6FE57 (subcategory_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE knowledge_base ADD CONSTRAINT FK_AF81B45512469DE2 FOREIGN KEY (category_id) REFERENCES knowledge_base_category (id)');
        $this->addSql('ALTER TABLE knowledge_base ADD CONSTRAINT FK_AF81B4555DC6FE57 FOREIGN KEY (subcategory_id) REFERENCES knowledge_base_subcategory (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE knowledge_base DROP FOREIGN KEY FK_AF81B45512469DE2');
        $this->addSql('ALTER TABLE knowledge_base DROP FOREIGN KEY FK_AF81B4555DC6FE57');
        $this->addSql('DROP TABLE knowledge_base_category');
        $this->addSql('DROP TABLE knowledge_base_subcategory');
        $this->addSql('DROP TABLE knowledge_base');
    }
}
