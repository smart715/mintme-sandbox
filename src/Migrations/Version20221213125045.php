<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221213125045 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Create internal transaction table';
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE internal_transaction (id INT AUTO_INCREMENT NOT NULL, token_id INT DEFAULT NULL, crypto_id INT DEFAULT NULL, crypto_network_id INT DEFAULT NULL, user_id INT DEFAULT NULL, date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', amount VARCHAR(255) NOT NULL, address VARCHAR(255) NOT NULL, fee VARCHAR(255) NOT NULL, fee_currency VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, INDEX IDX_4758455441DEE7B9 (token_id), INDEX IDX_47584554E9571A63 (crypto_id), INDEX IDX_4758455437423AA5 (crypto_network_id), INDEX IDX_47584554A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE internal_transaction ADD CONSTRAINT FK_4758455441DEE7B9 FOREIGN KEY (token_id) REFERENCES token (id)');
        $this->addSql('ALTER TABLE internal_transaction ADD CONSTRAINT FK_47584554E9571A63 FOREIGN KEY (crypto_id) REFERENCES crypto (id)');
        $this->addSql('ALTER TABLE internal_transaction ADD CONSTRAINT FK_4758455437423AA5 FOREIGN KEY (crypto_network_id) REFERENCES crypto (id)');
        $this->addSql('ALTER TABLE internal_transaction ADD CONSTRAINT FK_47584554A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE internal_transaction');
    }
}
