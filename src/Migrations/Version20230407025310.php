<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230407025310 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Wrapped USDC, ETH, BNB, CRO, BTC for Mintme blockchain';
    }

    public function up(Schema $schema): void
    {
        $values = [
            ['symbol' => 'BTC', 'address' => '0x67f44cb704884e0eb53c48fec8f1b1e7f8a63729'],
            ['symbol' => 'USDC', 'address' => '0xdcb579aa78e35e34581c72c291d493105949ac27'],
            ['symbol' => 'CRO', 'address' => '0xdfe3a98aa33c3393792e5e53e222f82f0b6482c0'],
            ['symbol' => 'BNB', 'address' => '0x89456efa718884f48b51f4790557b4981ffc0aa2'],
            ['symbol' => 'ETH', 'address' => '0x818938a83036b18a44f2ddf43d47454f6ae49bd6'],
        ];

        foreach ($values as $value) {
            $this->addSql('
                INSERT INTO wrapped_crypto_token (crypto_id, crypto_deploy_id, address, fee)
                VALUES (
                    (SELECT id FROM crypto WHERE symbol = :symbol),
                    (SELECT id FROM crypto where symbol  = "WEB"),
                    :address,
                    (SELECT fee FROM crypto WHERE symbol = "WEB")
                )
              ', ['symbol' => $value['symbol'], 'address' => $value['address']]);
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            DELETE FROM wrapped_crypto_token 
            WHERE token_name IN ("BTC", "USDC", "CRO", "BNB", "ETH")
            AND crypto_deploy_id = (SELECT id FROM crypto where symbol  = "WEB")
       ');
    }
}
