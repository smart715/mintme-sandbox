<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220830121739 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('UPDATE crypto SET image_path="/media/default_mintme.svg" WHERE symbol="WEB"');
        $this->addSql('UPDATE crypto SET image_path="/media/default_btc.svg" WHERE symbol="BTC"');
        $this->addSql('UPDATE crypto SET image_path="/media/default_eth.svg" WHERE symbol="ETH"');
        $this->addSql('UPDATE crypto SET image_path="/media/default_usdc.svg" WHERE symbol="USDC"');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('UPDATE crypto SET image_path="/media/default_mintme.png" WHERE symbol="WEB"');
        $this->addSql('UPDATE crypto SET image_path="/media/default_btc.png" WHERE symbol="BTC"');
        $this->addSql('UPDATE crypto SET image_path="/media/default_eth.png" WHERE symbol="ETH"');
        $this->addSql('UPDATE crypto SET image_path="/media/default_mintme.png" WHERE symbol="USDC"');
    }
}