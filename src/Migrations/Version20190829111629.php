<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190829111629 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE user_cryptos (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, crypto_id INT NOT NULL, created DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_DEB98E86A76ED395 (user_id), INDEX IDX_DEB98E86E9571A63 (crypto_id), UNIQUE INDEX user_crypto_index (user_id, crypto_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_cryptos ADD CONSTRAINT FK_DEB98E86A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_cryptos ADD CONSTRAINT FK_DEB98E86E9571A63 FOREIGN KEY (crypto_id) REFERENCES crypto (id)');
        $this->addSql('ALTER TABLE user_tokens DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE user_tokens ADD id INT AUTO_INCREMENT PRIMARY KEY NOT NULL, ADD created DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE UNIQUE INDEX user_token_index ON user_tokens (user_id, token_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE user_cryptos');
        $this->addSql('ALTER TABLE user_tokens MODIFY id INT NOT NULL');
        $this->addSql('DROP INDEX user_token_index ON user_tokens');
        $this->addSql('ALTER TABLE user_tokens DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE user_tokens DROP id, DROP created');
        $this->addSql('ALTER TABLE user_tokens ADD PRIMARY KEY (user_id, token_id)');
    }
}
