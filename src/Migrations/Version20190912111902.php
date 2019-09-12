<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190912111902 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE reciprocal_links (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, url VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_C137BECAF47645AE (url), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('DROP TABLE pending_token_withdraw');
        $this->addSql('ALTER TABLE token DROP min_destination, DROP min_destination_locked, DROP deploy_cost');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE pending_token_withdraw (id INT AUTO_INCREMENT NOT NULL, token_id INT DEFAULT NULL, user_id INT DEFAULT NULL, date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', amount VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, address VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, hash VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, INDEX IDX_3A875FAF41DEE7B9 (token_id), INDEX IDX_3A875FAFA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE pending_token_withdraw ADD CONSTRAINT FK_3A875FAF41DEE7B9 FOREIGN KEY (token_id) REFERENCES token (id)');
        $this->addSql('ALTER TABLE pending_token_withdraw ADD CONSTRAINT FK_3A875FAFA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('DROP TABLE reciprocal_links');
        $this->addSql('ALTER TABLE token ADD min_destination VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, ADD min_destination_locked TINYINT(1) DEFAULT \'0\' NOT NULL, ADD deploy_cost VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci');
    }
}
