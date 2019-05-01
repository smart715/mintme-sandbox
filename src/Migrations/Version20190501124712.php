<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190501124712 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE market_status (id INT AUTO_INCREMENT NOT NULL, crypto_id INT DEFAULT NULL, quote_token_id INT DEFAULT NULL, quote_crypto_id INT DEFAULT NULL, open_price VARCHAR(255) NOT NULL, last_price VARCHAR(255) NOT NULL, day_volume VARCHAR(255) NOT NULL, INDEX IDX_5BA4CE10E9571A63 (crypto_id), INDEX IDX_5BA4CE10ABE44F28 (quote_token_id), INDEX IDX_5BA4CE106EB58319 (quote_crypto_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE market_status ADD CONSTRAINT FK_5BA4CE10E9571A63 FOREIGN KEY (crypto_id) REFERENCES crypto (id)');
        $this->addSql('ALTER TABLE market_status ADD CONSTRAINT FK_5BA4CE10ABE44F28 FOREIGN KEY (quote_token_id) REFERENCES token (id)');
        $this->addSql('ALTER TABLE market_status ADD CONSTRAINT FK_5BA4CE106EB58319 FOREIGN KEY (quote_crypto_id) REFERENCES crypto (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE market_status');
    }
}
