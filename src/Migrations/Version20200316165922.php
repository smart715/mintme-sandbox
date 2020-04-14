<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200316165922 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE airdrop (
                                id INT AUTO_INCREMENT NOT NULL,
                                token_id INT NOT NULL,
                                status SMALLINT NOT NULL,
                                amount VARCHAR(100) NOT NULL,
                                locked_amount VARCHAR(100) NOT NULL,
                                participants INT NOT NULL,
                                end_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                                actual_amount VARCHAR(100) DEFAULT \'0\',
                                actual_participants INT DEFAULT 0,
                                INDEX IDX_4D15AF2C41DEE7B9 (token_id),
                                PRIMARY KEY(id)
                            )
                            DEFAULT CHARACTER SET utf8mb4
                            COLLATE utf8mb4_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql('ALTER TABLE airdrop ADD CONSTRAINT FK_4D15AF2C41DEE7B9 FOREIGN KEY (token_id) REFERENCES token (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE airdrop');
    }
}
