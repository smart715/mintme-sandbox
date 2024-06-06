<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210805160607 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Migrate exchange crypto id from Token table to TokenCrypto table and update crypto ids';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('INSERT INTO token_crypto (crypto_id, token_id, created) SELECT exchange_crypto_id, id, created FROM token;');
        $this->addSql('UPDATE token_crypto SET crypto_id = 1 WHERE crypto_id IS NULL;');
        $this->addSql('ALTER TABLE token DROP FOREIGN KEY FK_5F37A13BA47F8342');
        $this->addSql('DROP INDEX IDX_5F37A13BA47F8342 ON token');
        $this->addSql('ALTER TABLE token DROP exchange_crypto_id');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE token ADD exchange_crypto_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE token ADD CONSTRAINT FK_5F37A13BA47F8342 FOREIGN KEY (exchange_crypto_id) REFERENCES crypto (id)');
        $this->addSql('CREATE INDEX IDX_5F37A13BA47F8342 ON token (exchange_crypto_id)');
        $this->addSql('UPDATE token_crypto SET crypto_id = null WHERE crypto_id = 1');
        $this->addSql('UPDATE token t INNER JOIN token_crypto tc on t.id = tc.token_id SET t.exchange_crypto_id = t.crypto_id WHERE t.id = tc.token_id');
    }
}
