<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210921113816 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Rename donation columns for receiver and add receiver currency column';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE donation
                               CHANGE mintme_amount receiver_amount VARCHAR(255) DEFAULT NULL,
                               CHANGE mintme_fee_amount receiver_fee_amount VARCHAR(255) DEFAULT NULL;');
        $this->addSql('ALTER TABLE donation ADD receiver_currency VARCHAR(255) DEFAULT \'WEB\' NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE donation
                               CHANGE receiver_amount mintme_amount VARCHAR(255) DEFAULT NULL,
                               CHANGE receiver_fee_amount VARCHAR(255) DEFAULT NULL;');
        $this->addSql('ALTER TABLE donation DROP receiver_currency');
    }
}
