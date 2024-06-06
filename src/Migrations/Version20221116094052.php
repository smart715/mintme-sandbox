<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221116094052 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Fix date immutable type for all tables';
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
            ALTER TABLE reward_participants 
            CHANGE bonus_price bonus_price VARCHAR(255) DEFAULT NULL,
            CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'
        ');
        $this->addSql('ALTER TABLE reward_volunteers CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE airdrop_participants CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE phone_number CHANGE tem_phone_number tem_phone_number VARCHAR(35) DEFAULT NULL COMMENT \'(DC2Type:phone_number)\'');

        $this->addSql('ALTER TABLE comment CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE market_status CHANGE expires expires DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('
            ALTER TABLE blacklist_ip 
            CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', 
            CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'
        ');
        $this->addSql('ALTER TABLE airdrop_participants CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('
            ALTER TABLE finance_balance 
            CHANGE timestamp timestamp DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT \'(DC2Type:datetime_immutable)\'
        ');
        $this->addSql('ALTER TABLE auth_attempts CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE unsubscriber CHANGE date date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE user_login_info CHANGE date date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('
            ALTER TABLE post 
            CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', 
            CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\' on update CURRENT_TIMESTAMP
        ');
        $this->addSql('ALTER TABLE post_users_share_reward CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
            ALTER TABLE reward_participants 
            CHANGE bonus_price bonus_price VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, 
            CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL
        ');
        $this->addSql('ALTER TABLE reward_volunteers CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE phone_number CHANGE tem_phone_number tem_phone_number VARCHAR(35) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE comment CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE market_status CHANGE expires expires DATETIME DEFAULT NULL');
        $this->addSql('
            ALTER TABLE blacklist_ip 
            CHANGE created_at created_at DATETIME NOT NULL, 
            CHANGE updated_at updated_at DATETIME NOT NULL
        ');
        $this->addSql('ALTER TABLE finance_balance CHANGE timestamp timestamp DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE auth_attempts CHANGE updated_at updated_at DATETIME NOT NULL');

        $this->addSql('ALTER TABLE unsubscriber CHANGE date date DATETIME NOT NULL');
        $this->addSql('ALTER TABLE user_login_info CHANGE date date DATETIME NOT NULL');
        $this->addSql('
            ALTER TABLE post 
            CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, 
            CHANGE updated_at updated_at DATETIME DEFAULT NULL
        ');
        $this->addSql('ALTER TABLE airdrop_participants CHANGE created_at created_at DATETIME NOT NULL DEFAULT TIMESTAMP(0)');
    }
}
