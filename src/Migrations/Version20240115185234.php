<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240115185234 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Replace tos translation by key';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DELETE FROM translation WHERE translation_for = "tos" AND key_translation = "body.content.13.8"');
        $this->addSql('
            INSERT INTO `translation` (`position`, `translation_for`, `key_language`, `key_translation`, `content`)
            VALUES
            (118001, \'tos\', \'en\', \'body.content.13.8\', \'13.8. Assets Conversion Policy: In order to maintain a dynamic and active trading environment, a weekly conversion rate of 5% (or a 50 USD equivalent) will be applied to the crypto coin assets of accounts that have not engaged in any trading (buying or selling) and have not deposited any coin assets listed on <a class="link highlight" target="_blank" href="https://www.mintme.com/trade/coins">https://www.mintme.com/trade/coins</a> (except for MINTME) for a period of 3 consecutive months. This rate will be automatically converted to MINTME on the available MINTME/coin markets. This rate is applicable only to crypto coin assets, and does not affect any token assets or MINTME itself held in the account. Crypto coin assets refer to coins listed at <a class="link highlight" target="_blank" href="https://www.mintme.com/trade/coins">https://www.mintme.com/trade/coins</a>. \')
        ');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DELETE FROM translation WHERE translation_for = "tos" AND key_translation = "body.content.13.8"');
        $this->addSql('
            INSERT INTO `translation` (`position`, `translation_for`, `key_language`, `key_translation`, `content`)
            VALUES
            (118001, \'tos\', \'en\', \'body.content.13.8\', \'13.8. Assets Conversion Policy: In order to maintain a dynamic and active trading environment, a daily conversion rate of 0.5% (or a 5 USD equivalent) will be applied to the crypto coin assets of accounts that have not engaged in any trading (buying or selling) for a period of 3 consecutive months. This rate will be automatically converted to MINTME on the available MINTME/coin markets. This rate is applicable only to crypto coin assets, and does not affect any token assets or MINTME itself held in the account. Crypto coin assets refer to coins listed at <a class="link highlight" target="_blank" href="https://www.mintme.com/trade/coins">https://www.mintme.com/trade/coins</a>. \'),
            (118001, \'tos\', \'es\', \'body.content.13.8\', \'13.8. Política de conversión de activos: Con el fin de mantener un entorno de negociación dinámico y activo, se aplicará una tasa de conversión diaria del 0,5% (o un equivalente de 5 USD) a los activos de criptomonedas de las cuentas que no hayan realizado ninguna operación (compra o venta) durante un periodo de 3 meses consecutivos. Esta tasa se convertirá automáticamente a MINTME en los mercados de MINTME/monedas disponibles. Esta tasa sólo es aplicable a los activos de criptomonedas, y no afecta a los activos de tokens o al propio MINTME que se tenga en la cuenta. Los activos en criptomonedas se refieren a las monedas que figuran en <a class="link highlight" target="_blank" href="https://www.mintme.com/trade/coins">https://www.mintme.com/trade/coins</a>.\'),
            (118001, \'tos\', \'fr\', \'body.content.13.8\', \'13.8. Assets Conversion Policy: In order to maintain a dynamic and active trading environment, a daily conversion rate of 0.5% (or a 5 USD equivalent) will be applied to the crypto coin assets of accounts that have not engaged in any trading (buying or selling) for a period of 3 consecutive months. This rate will be automatically converted to MINTME on the available MINTME/coin markets. This rate is applicable only to crypto coin assets, and does not affect any token assets or MINTME itself held in the account. Crypto coin assets refer to coins listed at <a class="link highlight" target="_blank" href="https://www.mintme.com/trade/coins">https://www.mintme.com/trade/coins</a>.\')
        ');
    }
}
