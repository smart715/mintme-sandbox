<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230117142104 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Adding WMM/CRO';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs

        $this->addSql('
            INSERT INTO wrapped_crypto_token (crypto_id, crypto_deploy_id, address, fee) 
            VALUES (
                (SELECT id FROM crypto WHERE symbol = "WEB"), 
                (SELECT id FROM crypto where symbol  = "CRO"), 
                "0xdE9C449A4Db5792c0D4E2D7c8A9F6D15Ca8d782a",
                (SELECT fee FROM crypto WHERE symbol = "CRO")
            )
        ');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs

        $this->addSql('
            DELETE FROM wrapped_crypto_token WHERE
            crypto_id = (SELECT id FROM crypto WHERE symbol = "WEB") AND
            crypto_deploy_id = (SELECT id FROM crypto where symbol  = "CRO")
        ');
    }
}
