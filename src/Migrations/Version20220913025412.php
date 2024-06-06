<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220913025412 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'create token_signup_bonus_code table to manage sign up bonus codes and limits';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE token_signup_bonus_code (id INT AUTO_INCREMENT NOT NULL, token_id INT NOT NULL, amount VARCHAR(100) NOT NULL, participants int NOT NULL, code VARCHAR(100) NOT NULL, locked_amount VARCHAR(100) NOT NULL, UNIQUE INDEX UNIQ_BF14627941DEE7B9 (token_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE token_signup_bonus_code ADD CONSTRAINT FK_BF14627941DEE7B9 FOREIGN KEY (token_id) REFERENCES token (id)');
        $this->addSql('ALTER TABLE bonus ADD tradable_name VARCHAR(100) NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE token_signup_bonus_code');
        $this->addSql('ALTER TABLE bonus DROP tradable_name');
    }
}
