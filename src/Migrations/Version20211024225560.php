<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211024225560 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Adding crypto column to pending_token_withdraw';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('ALTER TABLE pending_token_withdraw ADD crypto_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pending_token_withdraw ADD CONSTRAINT FK_3A875FAFE9571A63 FOREIGN KEY (crypto_id) REFERENCES crypto (id)');
        $this->addSql('CREATE INDEX IDX_3A875FAFE9571A63 ON pending_token_withdraw (crypto_id)');

        // Updating pending withdraws with correct crypto
        $this->addSql('UPDATE pending_token_withdraw AS ptw
            JOIN token ON token.id = ptw.token_id
            SET ptw.crypto_id = token.crypto_id'
        );
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('ALTER TABLE pending_token_withdraw DROP FOREIGN KEY FK_3A875FAFE9571A63');
        $this->addSql('DROP INDEX IDX_3A875FAFE9571A63 ON pending_token_withdraw');
        $this->addSql('ALTER TABLE pending_token_withdraw DROP crypto_id');
    }
}
