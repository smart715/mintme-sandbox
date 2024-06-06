<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220412033516 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Adding Wrap MINTME (WMM) for ETH and BNB';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('
            INSERT INTO wrapped_crypto_token (crypto_id, crypto_deploy_id, address, fee) 
            VALUES (
                (SELECT id FROM crypto WHERE symbol = "WEB"), 
                (SELECT id FROM crypto where symbol  = "ETH"), 
                "0xd52b5b97e7096138831911f37e41017fda012434",
                (SELECT fee FROM crypto WHERE symbol = "ETH")
            )
        ');

        $this->addSql('
            INSERT INTO wrapped_crypto_token (crypto_id, crypto_deploy_id, address, fee) 
            VALUES (
                (SELECT id FROM crypto WHERE symbol = "WEB"), 
                (SELECT id FROM crypto where symbol  = "BNB"), 
                "0x353dBE9EA348d0cEf76BA75eF55CcAE5D0935a49",
                (SELECT fee FROM crypto WHERE symbol = "BNB")
            )
        ');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('DELETE FROM wrapped_crypto_token WHERE token_name = "WMM"');
    }
}
