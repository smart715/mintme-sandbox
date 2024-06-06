<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220412033120 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
            CREATE TABLE wrapped_crypto_token (
                id INT AUTO_INCREMENT NOT NULL,
                crypto_id INT NOT NULL,
                crypto_deploy_id INT NOT NULL,
                address VARCHAR(255) NOT NULL,
                fee VARCHAR(255) NOT NULL,
                INDEX IDX_D80B6DDAE9571A63 (crypto_id),
                INDEX IDX_D80B6DDA9ED55EF9 (crypto_deploy_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ');
        $this->addSql('ALTER TABLE wrapped_crypto_token ADD CONSTRAINT FK_D80B6DDAE9571A63 FOREIGN KEY (crypto_id) REFERENCES crypto (id)');
        $this->addSql('ALTER TABLE wrapped_crypto_token ADD CONSTRAINT FK_D80B6DDA9ED55EF9 FOREIGN KEY (crypto_deploy_id) REFERENCES crypto (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE wrapped_crypto_token');
    }
}
