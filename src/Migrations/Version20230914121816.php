<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230914121816 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Drop unique address index and create unique crypto/address index for \'token_deploy\' table';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX address ON token_deploy');
        $this->addSql('CREATE UNIQUE INDEX IDX_CRYPTO_ADDRESS ON token_deploy (crypto_id, address)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX IDX_CRYPTO_ADDRESS ON token_deploy');
        $this->addSql('CREATE UNIQUE INDEX address ON token_deploy (address)');
    }
}
