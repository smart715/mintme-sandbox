<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230131085256 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add crypto_id column. Set token_id and crypto_id columns to nullable';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE bonus_balance_transactions ADD crypto_id INT DEFAULT NULL, CHANGE token_id token_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE bonus_balance_transactions ADD CONSTRAINT FK_B55B9636E9571A63 FOREIGN KEY (crypto_id) REFERENCES crypto (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_B55B9636E9571A63 ON bonus_balance_transactions (crypto_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE bonus_balance_transactions DROP FOREIGN KEY FK_B55B9636E9571A63');
        $this->addSql('DROP INDEX IDX_B55B9636E9571A63 ON bonus_balance_transactions');
        $this->addSql('ALTER TABLE bonus_balance_transactions DROP crypto_id, CHANGE token_id token_id INT NOT NULL');
    }
}
