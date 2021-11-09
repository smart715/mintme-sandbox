<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191227140531 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE api__client (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, random_id VARCHAR(255) NOT NULL, redirect_uris LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', secret VARCHAR(255) NOT NULL, allowed_grant_types LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', INDEX IDX_D34997B1A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE api__access_token (id INT AUTO_INCREMENT NOT NULL, client_id INT NOT NULL, user_id INT DEFAULT NULL, token VARCHAR(255) NOT NULL, expires_at INT DEFAULT NULL, scope VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_44940A4D5F37A13B (token), INDEX IDX_44940A4D19EB6921 (client_id), INDEX IDX_44940A4DA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE api__refresh_token (id INT AUTO_INCREMENT NOT NULL, client_id INT NOT NULL, user_id INT DEFAULT NULL, token VARCHAR(255) NOT NULL, expires_at INT DEFAULT NULL, scope VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_8CB9C3055F37A13B (token), INDEX IDX_8CB9C30519EB6921 (client_id), INDEX IDX_8CB9C305A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE api__auth_code (id INT AUTO_INCREMENT NOT NULL, client_id INT NOT NULL, user_id INT DEFAULT NULL, token VARCHAR(255) NOT NULL, redirect_uri LONGTEXT NOT NULL, expires_at INT DEFAULT NULL, scope VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_D3050D205F37A13B (token), INDEX IDX_D3050D2019EB6921 (client_id), INDEX IDX_D3050D20A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE api__client ADD CONSTRAINT FK_D34997B1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE api__access_token ADD CONSTRAINT FK_44940A4D19EB6921 FOREIGN KEY (client_id) REFERENCES api__client (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE api__access_token ADD CONSTRAINT FK_44940A4DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE api__refresh_token ADD CONSTRAINT FK_8CB9C30519EB6921 FOREIGN KEY (client_id) REFERENCES api__client (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE api__refresh_token ADD CONSTRAINT FK_8CB9C305A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE api__auth_code ADD CONSTRAINT FK_D3050D2019EB6921 FOREIGN KEY (client_id) REFERENCES api__client (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE api__auth_code ADD CONSTRAINT FK_D3050D20A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE api__access_token DROP FOREIGN KEY FK_44940A4D19EB6921');
        $this->addSql('ALTER TABLE api__refresh_token DROP FOREIGN KEY FK_8CB9C30519EB6921');
        $this->addSql('ALTER TABLE api__auth_code DROP FOREIGN KEY FK_D3050D2019EB6921');
        $this->addSql('DROP TABLE api__client');
        $this->addSql('DROP TABLE api__access_token');
        $this->addSql('DROP TABLE api__refresh_token');
        $this->addSql('DROP TABLE api__auth_code');
    }
}
