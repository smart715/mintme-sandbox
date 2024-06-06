<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230224200426 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '#9215';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE token_release_address_history (
            id INT AUTO_INCREMENT NOT NULL, 
            token_id INT NOT NULL, 
            crypto_id INT NOT NULL, 
            user_id INT NOT NULL, 
            cost VARCHAR(255) NOT NULL, 
            old_address VARCHAR(255) DEFAULT NULL, 
            new_address VARCHAR(255) NOT NULL, 
            status VARCHAR(255) NOT NULL, 
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', 
            INDEX IDX_70A532E441DEE7B9 (token_id), 
            INDEX IDX_70A532E4E9571A63 (crypto_id), 
            INDEX IDX_70A532E4A76ED395 (user_id), 
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE token_release_address_history ADD CONSTRAINT FK_70A532E441DEE7B9 FOREIGN KEY (token_id) REFERENCES token (id)');
        $this->addSql('ALTER TABLE token_release_address_history ADD CONSTRAINT FK_70A532E4E9571A63 FOREIGN KEY (crypto_id) REFERENCES crypto (id)');
        $this->addSql('ALTER TABLE token_release_address_history ADD CONSTRAINT FK_70A532E4A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');

        $this->addSql('ALTER TABLE token_crypto ADD cost VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE token_crypto ADD crypto_cost_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE token_crypto ADD CONSTRAINT FK_69508787CCC32390 FOREIGN KEY (crypto_cost_id) REFERENCES crypto (id)');
        $this->addSql('CREATE INDEX IDX_69508787CCC32390 ON token_crypto (crypto_cost_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE token_release_address_history');

        $this->addSql('ALTER TABLE token_crypto DROP cost');
        $this->addSql('ALTER TABLE token_crypto DROP FOREIGN KEY FK_69508787CCC32390');
        $this->addSql('DROP INDEX IDX_69508787CCC32390 ON token_crypto');
        $this->addSql('ALTER TABLE token_crypto DROP crypto_cost_id');
    }
}
