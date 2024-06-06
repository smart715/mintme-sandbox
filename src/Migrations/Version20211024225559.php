<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211024225559 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'token_deploy table #7450';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE token_deploy (
            id INT AUTO_INCREMENT NOT NULL,
            token_id INT NOT NULL,
            crypto_id INT NOT NULL,
            address VARCHAR(255) DEFAULT NULL UNIQUE,
            tx_hash VARCHAR(255) DEFAULT NULL UNIQUE,
            deploy_cost VARCHAR(255) DEFAULT NULL,
            deploy_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_C13B62D841DEE7B9 (token_id),
            INDEX IDX_C13B62D8E9571A63 (crypto_id),
            UNIQUE INDEX IDX_TOKEN_CRYPTO (token_id, crypto_id),
            PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE token_deploy ADD CONSTRAINT FK_C13B62D841DEE7B9 FOREIGN KEY (token_id) REFERENCES token (id)');
        $this->addSql('ALTER TABLE token_deploy ADD CONSTRAINT FK_C13B62D8E9571A63 FOREIGN KEY (crypto_id) REFERENCES crypto (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE token_deploy');
    }
}
