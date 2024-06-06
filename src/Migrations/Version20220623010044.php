<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220623010044 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'add validation_code used for phone_number vor validate mail and sms codes';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs

        $this->addSql(
            'CREATE TABLE validation_code (
                id INT AUTO_INCREMENT NOT NULL,
                code VARCHAR(6) DEFAULT NULL,
                code_type VARCHAR(255) NOT NULL,
                daily_attempts INT NOT NULL,
                weekly_attempts INT NOT NULL,
                monthly_attempts INT NOT NULL,
                total_attempts INT NOT NULL,
                attempts_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                failed_attempts INT NOT NULL,
                send_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                phone_number_id INT DEFAULT NULL,
                PRIMARY KEY(id),
                CONSTRAINT FK_52SFW547AS57H85B FOREIGN KEY (phone_number_id) REFERENCES phone_number(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE phone_number DROP COLUMN verification_code, DROP COLUMN daily_attempts, DROP COLUMN weekly_attempts, DROP COLUMN monthly_attempts, DROP COLUMN attempts_date, DROP COLUMN total_attempts, DROP COLUMN send_code_date');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(
            'ALTER TABLE phone_number
                ADD COLUMN verification_code VARCHAR(6) NOT NULL,
                ADD COLUMN daily_attempts INT NOT NULL,
                ADD COLUMN weekly_attempts INT NOT NULL,
                ADD COLUMN monthly_attempts INT NOT NULL,
                ADD COLUMN attempts_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                ADD COLUMN total_attempts INT NOT NULL,
                ADD COLUMN send_code_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\''
        );
        $this->addSql('DROP TABLE validation_code');
    }
}
