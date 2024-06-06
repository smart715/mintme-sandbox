<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211024225561 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Migrating deploy data from token to token_deploy table';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
            INSERT INTO token_deploy (token_id, crypto_id, address, tx_hash, deploy_cost, deploy_date, created_at)
                SELECT id, crypto_id, address, tx_hash, deploy_cost, deployed_date, deployed_date FROM token
                WHERE crypto_id IS NOT NULL
                    AND address IS NOT NULL
                    AND address != "0x"
        ');

        $this->addSql('ALTER TABLE token DROP FOREIGN KEY FK_5F37A13BE9571A63');

        $this->addSql('
            ALTER TABLE token
            DROP crypto_id,
            DROP address,
            DROP tx_hash,
            DROP deploy_cost,
            DROP deployed_date 
        ');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
            ALTER TABLE token
            ADD COLUMN crypto_id int(11) DEFAULT NULL,
            ADD COLUMN address varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            ADD COLUMN tx_hash varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            ADD COLUMN deploy_cost varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            ADD COLUMN deployed_date datetime DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'
        ');

        $this->addSql('ALTER TABLE token ADD CONSTRAINT FK_5F37A13BE9571A63 FOREIGN KEY (crypto_id) REFERENCES crypto (id)');

        $this->addSql('
            UPDATE token
            INNER JOIN token_deploy AS td
                ON token.id = td.token_id
            SET token.crypto_id = td.crypto_id,
                token.address = td.address,
                token.tx_hash = td.tx_hash,
                token.deploy_cost = td.deploy_cost,
                token.deployed_date = td.deploy_date
        ');

        $this->addSql('DELETE FROM token_deploy');
    }
}
