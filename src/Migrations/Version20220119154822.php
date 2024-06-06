<?php declare(strict_types = 1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220119154822 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add the deposit_hash table to store old deposit hash and prevent multiple processing of a deposit';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE deposit_hash (id INT AUTO_INCREMENT NOT NULL, crypto_id INT NOT NULL, token_id INT DEFAULT NULL, user_id INT NOT NULL, hash VARCHAR(255) NOT NULL, INDEX IDX_E7405A6E9571A63 (crypto_id), INDEX IDX_E7405A641DEE7B9 (token_id), INDEX IDX_E7405A6A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE deposit_hash ADD CONSTRAINT FK_E7405A6E9571A63 FOREIGN KEY (crypto_id) REFERENCES crypto (id)');
        $this->addSql('ALTER TABLE deposit_hash ADD CONSTRAINT FK_E7405A641DEE7B9 FOREIGN KEY (token_id) REFERENCES token (id)');
        $this->addSql('ALTER TABLE deposit_hash ADD CONSTRAINT FK_E7405A6A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE deposit_hash');
    }
}