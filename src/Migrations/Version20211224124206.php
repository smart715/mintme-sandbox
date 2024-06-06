<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211224124206 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Added table for bonus balance transactions';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            'CREATE TABLE bonus_balance_transactions (
                    id INT AUTO_INCREMENT NOT NULL,
                    user_id INT NOT NULL,
                    token_id INT NOT NULL,
                    amount varchar(100) NOT NULL,
                    type VARCHAR(255) NOT NULL,
                    bonus_type VARCHAR(255) NOT NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT \'(DC2Type:datetime_immutable)\',
                    PRIMARY KEY(id),
                    CONSTRAINT FK_BonusBalanceTransactions_Users FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
                    CONSTRAINT FK_BonusBalanceTransactions_Tokens FOREIGN KEY (token_id) REFERENCES token(id) ON DELETE CASCADE
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE bonus_balance_transactions DROP FOREIGN KEY FK_BonusBalanceTransactions_Users');
        $this->addSql('ALTER TABLE bonus_balance_transactions DROP FOREIGN KEY FK_BonusBalanceTransactions_Tokens');

        $this->addSql('DROP TABLE bonus_balance_transactions');
    }
}
