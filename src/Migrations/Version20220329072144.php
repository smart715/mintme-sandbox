<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220329072144 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Create table finance_balance';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('CREATE TABLE finance_balance (id int(11) NOT NULL AUTO_INCREMENT, timestamp datetime DEFAULT current_timestamp(), crypto varchar(255) NOT NULL, blockchain_balance float NOT NULL, users_balance float NOT NULL, fee float DEFAULT NULL, fee_paid float DEFAULT NULL, withdraw_fee_to_pay float DEFAULT NULL, bot_balance float DEFAULT NULL, cold_wallet_balance float DEFAULT NULL, PRIMARY KEY (id));');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('DROP TABLE finance_balance;');
    }
}
